# Fake Bank API

Uma API bancária moderna, robusta e escalável desenvolvida em PHP com foco em alta performance, concorrência e arquitetura limpa. O projeto simula operações financeiras reais, como abertura assíncrona de contas, depósitos, gestão de chaves PIX, transferências em duas etapas (criação e confirmação) e consulta de extrato detalhado.

---

## Sumário

1. [Sobre o Projeto](#sobre-o-projeto)
2. [Arquitetura e Padrões de Projeto](#arquitetura-e-padrões-de-projeto)
3. [Tecnologias Utilizadas](#tecnologias-utilizadas)
4. [Como a Aplicação Foi Construída](#como-a-aplicação-foi-construída)
5. [Instalação e Execução](#instalação-e-execução)
6. [Coleção do Postman](#coleção-do-postman)
7. [Documentação das Rotas da API](#documentação-das-rotas-da-api)
   - [Rotas Públicas](#rotas-públicas)
   - [Autenticação](#autenticação)
   - [Contas e Extrato](#contas-e-extrato)
   - [Depósitos](#depósitos)
   - [PIX](#pix)
8. [Testes Automatizados](#testes-automatizados)

---

## Sobre o Projeto

O objetivo deste projeto é fornecer uma base sólida para serviços financeiros digitais. Cada requisição foi pensada para garantir consistência de dados, proteção de informações sensíveis e processamento desacoplado em segundo plano.

Operações pesadas ou que exigem validações externas, como criação de conta, efetivação de depósitos e transferências PIX, são processadas via filas assíncronas com Redis, liberando o cliente HTTP imediatamente e oferecendo consultas de status em tempo real.

---

## Arquitetura e Padrões de Projeto

A aplicação segue rigorosamente os princípios de **Clean Architecture** e **Domain-Driven Design (DDD)**, dividida em camadas com responsabilidades bem isoladas:

- **Domain (Domínio):** Contém as entidades centrais do negócio (`User`, `Account`, `Transaction`, `PixKey`), os objetos de valor (`Cpf`, `FullName`, `Password`, `AccountNumber`, `Money`) com suas regras de validação intrínsecas e as interfaces de repositórios. Essa camada não depende de nenhum framework externo.
- **Application (Casos de Uso e Jobs):** Orquestra o fluxo da aplicação. Cada ação do sistema possui um caso de uso dedicado (`CreateAccountUseCase`, `DepositMoneyUseCase`, `CreatePixTransferUseCase`, `GetAccountStatementUseCase`, etc.) e jobs de fila que executam o processamento assíncrono.
- **Infrastructure (Infraestrutura):** Implementa o acesso ao banco de dados PostgreSQL, filas no Redis, geração e validação de tokens JWT e serviços de auditoria.
- **Interfaces / Http (Apresentação):** Controladores, rotas e middlewares HTTP (autenticação JWT, rate limiting e logs de auditoria).

### Destaques Arquiteturais

- **Desacoplamento por Interfaces:** Repositórios e serviços são injetados via interfaces, facilitando manutenções e testes unitários.
- **Consistência e Imutabilidade:** Uso de Value Objects para garantir que CPFs inválidos, nomes fora do padrão ou valores monetários negativos sequer consigam entrar no domínio.
- **Mascaramento Automático:** Dados sensíveis como CPF são automaticamente mascarados nas respostas públicas e extratos (exemplo: `529.***.***-25`).
- **Resiliência:** Operações em lote e concorrência tratadas com filas assíncronas e transações de banco de dados para evitar inconsistência de saldo.

---

## Tecnologias Utilizadas

- **PHP 8.5+:** Linguagem base, utilizando recursos modernos de tipagem estrita, atributos e promoção de propriedades de construtor.
- **Hyperf Framework:** Framework orientado a microserviços e alta performance, baseado no motor de corrotinas do Swoole.
- **Swoole:** Engine de rede orientada a eventos e corrotinas para PHP, proporcionando alto rendimento com I/O não bloqueante.
- **PostgreSQL:** Banco de dados relacional para persistência transacional com segurança ACID.
- **Redis:** Utilizado como broker de filas assíncronas (Hyperf AsyncQueue) e controle de taxa de requisições (Rate Limit).
- **JWT:** Autenticação segura via tokens Bearer assinados.
- **PHPUnit 11 e Mockery:** Suíte completa de testes unitários cobrindo todos os casos de uso.

---

## Como a Aplicação Foi Construída

O desenvolvimento foi guiado por passos iterativos para simular a experiência de um banco real:

1. **Modelagem de Domínio:** Criação dos Value Objects de CPF (com validação completa de dígitos verificadores), senhas criptografadas com bcrypt e controle de valores monetários centavo a centavo.
2. **Ciclo de Vida da Conta:** Ao criar uma conta, o registro inicial recebe o status `pending_creation` e um job é enviado para a fila Redis. O worker processa a abertura, cria o registro bancário, gera o número da conta e atualiza o status para `active`.
3. **Segurança e Auditoria:** Foi implementado middleware de Rate Limiting para prevenir abusos, autenticação JWT para rotas restritas e logs de auditoria em cada operação financeira.
4. **Fluxo de Depósito Inteligente:** O endpoint aceita informar uma conta de destino ou, caso omitida, deposita automaticamente na conta da pessoa autenticada. O depósito entra com status `pending` e o job credita o saldo de maneira atômica.
5. **PIX em Duas Etapas:** Para prevenir fraudes e erros de digitação, o envio de PIX é dividido em:
   - **Etapa 1 (Criação):** O usuário informa a chave ou conta e o valor. O sistema localiza o destinatário, cria a transação com status `created` e retorna os dados do recebedor com CPF mascarado para conferência.
   - **Etapa 2 (Confirmação):** Com o identificador em mãos, o usuário confirma o envio. O saldo é verificado, o status avança para `processing` e a transação entra na fila de compensação.
6. **Extrato Detalhado:** Permite visualizar todas as movimentações financeiras com identificador único, tipo de transação, dados do remetente (`origin`), do recebedor (`recipient` e `receipt`) e data formatada.
7. **Padronização de Identificadores:** Todas as rotas, parâmetros de URL e respostas JSON utilizam o termo `identifier` para referenciar transações e usuários.
8. **Valores Financeiros em Centavos:** Todas as rotas que envolvem dinheiro (depósitos e transferências PIX) aceitam exclusivamente valores inteiros representando centavos (por exemplo, 1000 equivale a R$ 10,00). Valores decimais ou flutuantes são sumariamente rejeitados para evitar imprecisões financeiras de arredondamento.

---

## Instalação e Execução

### Pré-requisitos

- Docker e Docker Compose instalados, ou ambiente local com PHP 8.2+, extensão Swoole ou Swow, PostgreSQL e Redis.

### Passo a passo com Docker

1. Clone o repositório e acesse a pasta do projeto:
```bash
git clone https://github.com/douglasendrew/fake-bank-api.git
cd fake-bank-api
```

2. Instale as dependências do Composer:
```bash
composer install
```

3. Configure o arquivo de ambiente:
```bash
cp .env.example .env
```
Ajuste as credenciais do PostgreSQL e do Redis conforme o seu ambiente.

4. Execute as migrações do banco de dados:
```bash
php bin/hyperf.php migrate
```

5. Inicie o servidor da aplicação:
```bash
php bin/hyperf.php start
```

O servidor estará disponível por padrão em `http://127.0.0.1:9501`.

---

## Coleção do Postman

Para facilitar os testes e a exploração prática de todas as rotas da API, disponibilizamos uma coleção completa do Postman pronta para importação no diretório `docs/`:

- Arquivo: [`docs/Fake Bank - v1.0.0.postman_collection.json`](file://docs/Fake%20Bank%20-%20v1.0.0.postman_collection.json)

Basta abrir o Postman, clicar em **Import** e selecionar esse arquivo JSON. Todas as requisições estão organizadas em pastas (Account, Auth, Deposit e PIX), acompanhadas de exemplos de corpo de requisição, parâmetros e variáveis configuráveis.

---

## Documentação das Rotas da API

Todas as rotas da versão 1 possuem o prefixo `/api/v1`.

### Rotas Públicas

#### 1. Status da API
Retorna o status geral de funcionamento do serviço.

- **Método:** `GET`
- **URL:** `/`
- **Resposta:**
```json
{
  "name": "Fake Bank API",
  "version": "1.0.0",
  "status": "running"
}
```

---

#### 2. Solicitar Abertura de Conta
Envia os dados cadastrais para a fila de processamento de abertura de conta.

- **Método:** `POST`
- **URL:** `/api/v1/accounts`
- **Corpo da Requisição:**
```json
{
  "name": "Douglas Sousa",
  "cpf": "52998224725",
  "password": "senhaSegura123"
}
```
- **Resposta (201 Created):**
```json
{
  "success": true,
  "message": "Account creation requested successfully and sent to processing queue.",
  "data": {
    "identifier": "01955f1a-b342-7a0e-9132-0242ac120002",
    "name": "Douglas Sousa",
    "cpf": "529.***.***-25",
    "status": "pending_creation"
  }
}
```

---

#### 3. Consultar Status da Criação de Conta
Permite acompanhar o andamento da abertura de conta usando o identificador recebido na criação.

- **Método:** `GET`
- **URL:** `/api/v1/accounts/status/{identifier}`
- **Parâmetros de URL:**
  - `identifier`: Identificador único do usuário.
- **Resposta (200 OK):**
```json
{
  "success": true,
  "data": {
    "identifier": "01955f1a-b342-7a0e-9132-0242ac120002",
    "name": "Douglas Sousa",
    "cpf": "529.***.***-25",
    "status": "active"
  }
}
```

---

#### 4. Atualizar Cadastro
Permite reenviar ou corrigir informações cadastrais da conta.

- **Método:** `PUT`
- **URL:** `/api/v1/accounts/{identifier}`
- **Parâmetros de URL:**
  - `identifier`: Identificador único do usuário.
- **Corpo da Requisição:**
```json
{
  "name": "Douglas Endrew",
  "cpf": "52998224725",
  "password": "010203"
}
```
- **Resposta (200 OK):**
```json
{
  "success": true,
  "message": "Account information resubmitted for processing.",
  "data": {
    "identifier": "01955f1a-b342-7a0e-9132-0242ac120002",
    "name": "Douglas Endrew",
    "cpf": "529.***.***-25",
    "status": "active"
  }
}
```

---

### Autenticação

#### Login
Autentica o usuário pelo CPF e senha, gerando o token JWT para as rotas protegidas.

- **Método:** `POST`
- **URL:** `/api/v1/auth/login`
- **Corpo da Requisição:**
```json
{
  "cpf": "52998224725",
  "password": "senhaSegura123"
}
```
- **Resposta (200 OK):**
```json
{
  "success": true,
  "message": "Authentication successful.",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 900,
    "user": {
      "identifier": "01955f1a-b342-7a0e-9132-0242ac120002",
      "name": "Douglas Sousa",
      "cpf": "529.***.***-25",
      "status": "active"
    }
  }
}
```

Para as próximas rotas protegidas, inclua o cabeçalho:
`Authorization: Bearer <seu_token_jwt>`

---

### Contas e Extrato

#### 1. Meus Dados e Saldo
Consulta os dados cadastrais, número da conta e saldo atual da pessoa autenticada.

- **Método:** `GET`
- **URL:** `/api/v1/accounts/me`
- **Resposta (200 OK):**
```json
{
  "success": true,
  "data": {
    "name": "Douglas Sousa",
    "cpf": "529.***.***-25",
    "account_number": "100234567-8",
    "balance": 1500.50
  }
}
```

---

#### 2. Extrato Bancário
Retorna o histórico completo de transações da conta. Para saídas PIX exibe os dados do recebedor e comprovante, para entradas PIX exibe a origem e para depósitos exibe o número da conta favorecida.

- **Método:** `GET`
- **URL:** `/api/v1/accounts/statement` (ou `/api/v1/accounts/extrato`)
- **Resposta (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "identifier": "01955f30-848e-7e9b-9801-124930129011",
      "type": "pix_out",
      "amount": 75.00,
      "status": "completed",
      "data": {
        "recipient": {
          "name": "Maria Santos",
          "cpf": "389.***.***-88",
          "account_number": "200987654-1"
        },
        "receipt": {
          "name": "Maria Santos",
          "cpf": "389.***.***-88",
          "account_number": "200987654-1"
        }
      },
      "date": "2026-09-06T00:45:00+00:00"
    },
    {
      "identifier": "01955f2d-4512-701a-9fa1-987654321000",
      "type": "deposit",
      "amount": 500.00,
      "status": "completed",
      "data": {
        "account_number": "100234567-8"
      },
      "date": "2026-09-06T00:10:00+00:00"
    }
  ]
}
```

---

### Depósitos

#### 1. Realizar Depósito
Inicia uma solicitação de depósito. Se o campo `account_number` não for enviado, o depósito é creditado automaticamente na própria conta do usuário autenticado. O campo `amount` aceita exclusivamente valores inteiros em centavos (por exemplo: 25000 equivale a R$ 250,00; valores decimais ou flutuantes não são permitidos).

- **Método:** `POST`
- **URL:** `/api/v1/accounts/deposit`
- **Corpo da Requisição (depositando na própria conta):**
```json
{
  "amount": 25000
}
```
- **Corpo da Requisição (depositando em outra conta):**
```json
{
  "account_number": "200987654-1",
  "amount": 25000
}
```
- **Resposta (202 Accepted):**
```json
{
  "success": true,
  "data": {
    "message": "Deposit requested and sent to processing queue.",
    "identifier": "01955f2d-4512-701a-9fa1-987654321000",
    "account_number": "100234567-8",
    "amount": 250.00,
    "status": "pending"
  }
}
```

---

#### 2. Consultar Status do Depósito
Consulta a situação do depósito através do identificador da transação.

- **Método:** `GET`
- **URL:** `/api/v1/deposits/status/{identifier}` (ou `/api/v1/accounts/deposit/status/{identifier}`)
- **Parâmetros de URL:**
  - `identifier`: Identificador da transação retornado no momento do depósito.
- **Resposta (200 OK):**
```json
{
  "success": true,
  "data": {
    "identifier": "01955f2d-4512-701a-9fa1-987654321000",
    "type": "deposit",
    "account_number": "100234567-8",
    "amount": 250.00,
    "status": "completed"
  }
}
```

---

### PIX

#### 1. Listar Chaves PIX
Lista todas as chaves cadastradas da conta autenticada.

- **Método:** `GET`
- **URL:** `/api/v1/pix/keys`
- **Resposta (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "identifier": "01955f35-9011-7a1a-bb12-456789012345",
      "type": "cpf",
      "key": "52998224725",
      "created_at": "2026-09-06T00:20:00+00:00"
    }
  ]
}
```

---

#### 2. Cadastrar Chave PIX
Cadastra uma nova chave PIX para a conta. Tipos permitidos: `cpf`, `email`, `phone` ou `random`.

- **Método:** `POST`
- **URL:** `/api/v1/pix/keys`
- **Corpo da Requisição:**
```json
{
  "pix_type": "cpf",
  "pix_key": "52998224725"
}
```
- **Resposta (201 Created):**
```json
{
  "success": true,
  "message": "PIX key registered successfully.",
  "data": {
    "identifier": "01955f35-9011-7a1a-bb12-456789012345",
    "type": "cpf",
    "key": "52998224725",
    "created_at": "2026-09-06T00:20:00+00:00"
  }
}
```

---

#### 3. Remover Chave PIX
Remove uma chave cadastrada.

- **Método:** `DELETE`
- **URL:** `/api/v1/pix/keys/{key}`
- **Parâmetros de URL:**
  - `key`: Chave PIX cadastrada (exemplo: `52998224725`).
- **Resposta (200 OK):**
```json
{
  "success": true,
  "data": {
    "message": "PIX key deleted successfully."
  }
}
```

---

#### 4. Criar Transferência PIX (Etapa 1)
Cria a intenção de transferência PIX com status `created`. O campo `amount` aceita exclusivamente números inteiros em centavos (por exemplo: 7500 equivale a R$ 75,00; valores decimais ou flutuantes não são permitidos). O sistema busca quem vai receber e retorna o nome completo, CPF mascarado e número da conta para conferência.

- **Método:** `POST`
- **URL:** `/api/v1/pix/create`
- **Corpo da Requisição:**
```json
{
  "pix_key": "38927154088",
  "pix_type": "cpf",
  "amount": 7500
}
```
- **Resposta (201 Created):**
```json
{
  "success": true,
  "data": {
    "message": "PIX transfer created. Review recipient details and confirm to proceed.",
    "identifier": "01955f39-11aa-7001-9922-334455667788",
    "amount": 75.00,
    "status": "created",
    "recipient": {
      "name": "Maria Santos",
      "cpf": "389.***.***-88",
      "account_number": "200987654-1"
    }
  }
}
```

---

#### 5. Confirmar Transferência PIX (Etapa 2)
Confirma a transferência criada na primeira etapa usando o identificador retornado. A transação muda seu status para `processing` e vai para a fila assíncrona para dedução e crédito dos valores.

- **Método:** `POST`
- **URL:** `/api/v1/pix/confirm`
- **Corpo da Requisição:**
```json
{
  "identifier": "01955f39-11aa-7001-9922-334455667788"
}
```
- **Resposta (202 Accepted):**
```json
{
  "success": true,
  "data": {
    "message": "PIX transfer confirmed and queued for processing successfully.",
    "identifier": "01955f39-11aa-7001-9922-334455667788",
    "amount": 75.00,
    "status": "processing",
    "recipient": {
      "name": "Maria Santos",
      "cpf": "389.***.***-88",
      "account_number": "200987654-1"
    }
  }
}
```

---

#### 6. Consultar Status do PIX
Consulta o status de processamento da transferência PIX. O retorno apresenta informações completas de quem enviou (`origin`) e de quem recebeu (`recipient`).

- **Método:** `GET`
- **URL:** `/api/v1/pix/status/{identifier}` (ou `/api/v1/pix/transfer/status/{identifier}`)
- **Parâmetros de URL:**
  - `identifier`: Identificador da transação PIX.
- **Resposta (200 OK):**
```json
{
  "success": true,
  "data": {
    "identifier": "01955f39-11aa-7001-9922-334455667788",
    "type": "pix_transfer",
    "amount": 75.00,
    "status": "completed",
    "origin": {
      "name": "Douglas Sousa",
      "cpf": "529.***.***-25",
      "account_number": "100234567-8"
    },
    "recipient": {
      "name": "Maria Santos",
      "cpf": "389.***.***-88",
      "account_number": "200987654-1"
    },
    "created_at": "2026-09-06T00:30:00+00:00",
    "updated_at": "2026-09-06T00:30:02+00:00"
  }
}
```

---

## Testes Automatizados

O projeto possui uma suíte completa de testes unitários cobrindo todos os casos de uso de abertura de conta, depósitos, autenticação e fluxos do PIX com uso de mocks e asserções rigorosas.

Para executar todos os testes:

```bash
./vendor/bin/phpunit
```

Para rodar com relatório detalhado por teste:

```bash
./vendor/bin/phpunit --testdox
```

---

## Licença

Este projeto é disponibilizado sob a licença MIT. Sinta-se livre para usar, contribuir e expandir!

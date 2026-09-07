<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Interfaces\Http\Controllers;

use App\Application\UseCases\Account\CreateAccountUseCase;
use App\Application\UseCases\Account\GetAccountInfoUseCase;
use App\Application\UseCases\Account\GetAccountStatementUseCase;
use App\Application\UseCases\Account\GetAccountStatusUseCase;
use App\Application\UseCases\Account\UpdateAccountUseCase;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;

class AccountController
{
    public function __construct(
        private CreateAccountUseCase $createAccountUseCase,
        private GetAccountStatusUseCase $getAccountStatusUseCase,
        private UpdateAccountUseCase $updateAccountUseCase,
        private GetAccountInfoUseCase $getAccountInfoUseCase,
        private GetAccountStatementUseCase $getAccountStatementUseCase
    ) {
    }

    public function getStatement(RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $userUuid = (string) $request->getAttribute('user_uuid');
        $result = $this->getAccountStatementUseCase->execute($userUuid);

        return $response->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function create(RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $name = (string) $request->input('name', '');
        $cpf = (string) $request->input('cpf', '');
        $password = (string) $request->input('password', '');

        $result = $this->createAccountUseCase->execute($name, $cpf, $password);

        return $response->json([
            'success' => true,
            'message' => 'Account creation requested successfully and sent to processing queue.',
            'data' => $result,
        ])->withStatus(201);
    }

    public function getStatus(string $identifier, HttpResponse $response): ResponseInterface
    {
        $result = $this->getAccountStatusUseCase->execute($identifier);

        return $response->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function update(string $identifier, RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $name = (string) $request->input('name', '');
        $cpf = (string) $request->input('cpf', '');
        $password = (string) $request->input('password', '');

        $result = $this->updateAccountUseCase->execute($identifier, $name, $cpf, $password);

        return $response->json([
            'success' => true,
            'message' => 'Account information resubmitted for processing.',
            'data' => $result,
        ]);
    }

    public function me(RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $userUuid = (string) $request->getAttribute('user_uuid');
        $result = $this->getAccountInfoUseCase->execute($userUuid);

        return $response->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}

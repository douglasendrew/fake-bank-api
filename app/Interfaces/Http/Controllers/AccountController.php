<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controllers;

use App\Application\UseCases\Account\CreateAccountUseCase;
use App\Application\UseCases\Account\GetAccountInfoUseCase;
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
        private GetAccountInfoUseCase $getAccountInfoUseCase
    ) {}

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

    public function getStatus(string $uuid, HttpResponse $response): ResponseInterface
    {
        $result = $this->getAccountStatusUseCase->execute($uuid);

        return $response->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function update(string $uuid, RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $name = (string) $request->input('name', '');
        $cpf = (string) $request->input('cpf', '');
        $password = (string) $request->input('password', '');

        $result = $this->updateAccountUseCase->execute($uuid, $name, $cpf, $password);

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

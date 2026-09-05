<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controllers;

use App\Application\UseCases\Auth\AuthenticateUserUseCase;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;

class AuthController
{
    public function __construct(
        private AuthenticateUserUseCase $authenticateUserUseCase
    ) {}

    public function login(RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $cpf = (string) $request->input('cpf', '');
        $password = (string) $request->input('password', '');

        $result = $this->authenticateUserUseCase->execute($cpf, $password);

        return $response->json([
            'success' => true,
            'message' => 'Authentication successful.',
            'data' => $result,
        ]);
    }
}

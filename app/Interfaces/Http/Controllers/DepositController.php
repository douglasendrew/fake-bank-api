<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controllers;

use App\Application\UseCases\Deposit\DepositMoneyUseCase;
use App\Application\UseCases\Deposit\GetDepositStatusUseCase;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;

class DepositController
{
    public function __construct(
        private DepositMoneyUseCase $depositMoneyUseCase,
        private GetDepositStatusUseCase $getDepositStatusUseCase
    ) {}

    public function deposit(RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $userUuid = (string) $request->getAttribute('user_uuid');
        $accountNumber = (string) $request->input('account_number', '');
        $amount = (float) $request->input('amount', 0.0);

        $result = $this->depositMoneyUseCase->execute($accountNumber, $amount, $userUuid);

        return $response->json([
            'success' => true,
            'data' => $result,
        ])->withStatus(202);
    }

    public function getStatus(string $identifier, HttpResponse $response): ResponseInterface
    {
        $result = $this->getDepositStatusUseCase->execute($identifier);

        return $response->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}

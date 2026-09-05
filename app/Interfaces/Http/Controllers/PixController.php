<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controllers;

use App\Application\UseCases\Pix\DeletePixKeyUseCase;
use App\Application\UseCases\Pix\RegisterPixKeyUseCase;
use App\Application\UseCases\Pix\SendPixTransferUseCase;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;

class PixController
{
    public function __construct(
        private RegisterPixKeyUseCase $registerPixKeyUseCase,
        private SendPixTransferUseCase $sendPixTransferUseCase,
        private DeletePixKeyUseCase $deletePixKeyUseCase
    ) {}

    public function registerKey(RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $userUuid = (string) $request->getAttribute('user_uuid');
        $type = (string) $request->input('type', '');
        $key = (string) $request->input('key', '');

        $result = $this->registerPixKeyUseCase->execute($userUuid, $type, $key);

        return $response->json([
            'success' => true,
            'message' => 'PIX key registered successfully.',
            'data' => $result,
        ])->withStatus(201);
    }

    public function sendTransfer(RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $senderUuid = (string) $request->getAttribute('user_uuid');
        $target = (string) $request->input('target', $request->input('key', $request->input('account_number', '')));
        $type = (string) $request->input('type', '');
        $amount = (float) $request->input('amount', 0.0);

        $result = $this->sendPixTransferUseCase->execute($senderUuid, $target, $type, $amount);

        return $response->json([
            'success' => true,
            'data' => $result,
        ])->withStatus(202);
    }

    public function deleteKey(string $key, RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $userUuid = (string) $request->getAttribute('user_uuid');
        $result = $this->deletePixKeyUseCase->execute($userUuid, urldecode($key));

        return $response->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}

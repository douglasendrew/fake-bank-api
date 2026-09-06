<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controllers;

use App\Application\UseCases\Pix\ConfirmPixTransferUseCase;
use App\Application\UseCases\Pix\CreatePixTransferUseCase;
use App\Application\UseCases\Pix\DeletePixKeyUseCase;
use App\Application\UseCases\Pix\GetPixTransferStatusUseCase;
use App\Application\UseCases\Pix\ListPixKeysUseCase;
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
        private DeletePixKeyUseCase $deletePixKeyUseCase,
        private GetPixTransferStatusUseCase $getPixTransferStatusUseCase,
        private ListPixKeysUseCase $listPixKeysUseCase,
        private CreatePixTransferUseCase $createPixTransferUseCase,
        private ConfirmPixTransferUseCase $confirmPixTransferUseCase
    ) {}

    public function listKeys(RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $userUuid = (string) $request->getAttribute('user_uuid');
        $result = $this->listPixKeysUseCase->execute($userUuid);

        return $response->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function registerKey(RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $userUuid = (string) $request->getAttribute('user_uuid');
        $type = (string) $request->input('pix_type', $request->input('type', ''));
        $key = (string) $request->input('pix_key', $request->input('key', ''));

        $result = $this->registerPixKeyUseCase->execute($userUuid, $type, $key);

        return $response->json([
            'success' => true,
            'message' => 'PIX key registered successfully.',
            'data' => $result,
        ])->withStatus(201);
    }

    public function createTransfer(RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $senderUuid = (string) $request->getAttribute('user_uuid');
        $target = (string) $request->input('pix_key', $request->input('target', $request->input('key', $request->input('account_number', ''))));
        $type = (string) $request->input('pix_type', $request->input('type', ''));
        $amount = $request->input('amount');

        $result = $this->createPixTransferUseCase->execute($senderUuid, $target, $type, $amount);

        return $response->json([
            'success' => true,
            'data' => $result,
        ])->withStatus(201);
    }

    public function confirmTransfer(RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        $senderUuid = (string) $request->getAttribute('user_uuid');
        $transactionUuid = (string) $request->input('identifier', $request->input('uuid', $request->input('transaction_uuid', '')));

        $result = $this->confirmPixTransferUseCase->execute($senderUuid, $transactionUuid);

        return $response->json([
            'success' => true,
            'data' => $result,
        ])->withStatus(202);
    }

    public function sendTransfer(RequestInterface $request, HttpResponse $response): ResponseInterface
    {
        return $this->createTransfer($request, $response);
    }

    public function getTransferStatus(string $identifier, HttpResponse $response): ResponseInterface
    {
        $result = $this->getPixTransferStatusUseCase->execute($identifier);

        return $response->json([
            'success' => true,
            'data' => $result,
        ]);
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

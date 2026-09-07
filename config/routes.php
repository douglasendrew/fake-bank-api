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
use App\Interfaces\Http\Controllers\AccountController;
use App\Interfaces\Http\Controllers\AuthController;
use App\Interfaces\Http\Controllers\DepositController;
use App\Interfaces\Http\Controllers\PixController;
use App\Interfaces\Http\Middleware\ActionLoggingMiddleware;
use App\Interfaces\Http\Middleware\JwtAuthMiddleware;
use App\Interfaces\Http\Middleware\RateLimitMiddleware;
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', function () {
    return [
        'name' => 'Fake Bank API',
        'version' => '1.0.0',
        'status' => 'running',
    ];
});

// Global API Group with Rate Limiting & Action Audit Logging
Router::addGroup('/api/v1', function () {
    // Public Auth & Registration routes
    Router::post('/accounts', [AccountController::class, 'create']);
    Router::get('/accounts/status/{identifier}', [AccountController::class, 'getStatus']);
    Router::put('/accounts/{identifier}', [AccountController::class, 'update']);
    Router::post('/auth/login', [AuthController::class, 'login']);

    // Protected JWT authenticated routes
    Router::addGroup('', function () {
        Router::get('/accounts/me', [AccountController::class, 'me']);
        Router::get('/accounts/statement', [AccountController::class, 'getStatement']);
        Router::get('/accounts/extrato', [AccountController::class, 'getStatement']);
        Router::post('/accounts/deposit', [DepositController::class, 'deposit']);
        Router::get('/accounts/deposit/status/{identifier}', [DepositController::class, 'getStatus']);
        Router::get('/deposits/status/{identifier}', [DepositController::class, 'getStatus']);
        Router::get('/pix/keys', [PixController::class, 'listKeys']);
        Router::post('/pix/keys', [PixController::class, 'registerKey']);
        Router::post('/pix/create', [PixController::class, 'createTransfer']);
        Router::post('/pix/confirm', [PixController::class, 'confirmTransfer']);
        Router::post('/pix/transfer', [PixController::class, 'sendTransfer']);
        Router::get('/pix/transfer/status/{identifier}', [PixController::class, 'getTransferStatus']);
        Router::get('/pix/status/{identifier}', [PixController::class, 'getTransferStatus']);
        Router::delete('/pix/keys/{key}', [PixController::class, 'deleteKey']);
    }, [
        'middleware' => [JwtAuthMiddleware::class],
    ]);
}, [
    'middleware' => [RateLimitMiddleware::class, ActionLoggingMiddleware::class],
]);

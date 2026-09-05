<?php

declare(strict_types=1);

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
    Router::get('/accounts/status/{uuid}', [AccountController::class, 'getStatus']);
    Router::put('/accounts/{uuid}', [AccountController::class, 'update']);
    Router::post('/auth/login', [AuthController::class, 'login']);

    // Protected JWT authenticated routes
    Router::addGroup('', function () {
        Router::get('/accounts/me', [AccountController::class, 'me']);
        Router::post('/accounts/deposit', [DepositController::class, 'deposit']);
        Router::post('/pix/keys', [PixController::class, 'registerKey']);
        Router::post('/pix/transfer', [PixController::class, 'sendTransfer']);
        Router::delete('/pix/keys/{key}', [PixController::class, 'deleteKey']);
    }, [
        'middleware' => [JwtAuthMiddleware::class],
    ]);

}, [
    'middleware' => [RateLimitMiddleware::class, ActionLoggingMiddleware::class],
]);

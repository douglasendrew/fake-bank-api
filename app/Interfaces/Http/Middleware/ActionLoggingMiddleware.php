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

namespace App\Interfaces\Http\Middleware;

use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Logging\Entities\LogAction;
use App\Domain\Logging\Repositories\LogActionRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ActionLoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LogActionRepositoryInterface $logActionRepository,
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $path = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());

        // Skip logging for health checks or non-API routes if any
        if (str_starts_with($path, '/favicon') || $path === '/') {
            return $response;
        }

        $statusCode = $response->getStatusCode();
        $isError = $statusCode >= 400;

        $userUuid = $request->getAttribute('user_uuid');
        $userId = null;
        if ($userUuid) {
            $user = $this->userRepository->findByUuid($userUuid);
            $userId = $user?->getId();
        }

        $actionName = sprintf('%s_%s', $method, strtoupper(trim($path, '/')));

        $metadata = [
            'method' => $method,
            'path' => $path,
            'status_code' => $statusCode,
            'query' => $request->getQueryParams(),
        ];

        $logAction = new LogAction(
            action: $actionName,
            actionMetadata: $metadata,
            error: $isError,
            userId: $userId,
            errorId: null
        );

        try {
            $this->logActionRepository->save($logAction);
        } catch (Throwable $e) {
            // Ignore DB log failures during local execution without DB
        }

        return $response;
    }
}

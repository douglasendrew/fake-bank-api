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

use App\Infrastructure\Security\JwtService;
use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JwtService $jwtService
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (! preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $response = ApplicationContext::getContainer()->get(HttpResponse::class);
            return $response->json([
                'error' => true,
                'message' => 'Authorization header with Bearer token is missing.',
            ])->withStatus(401);
        }

        $jwtToken = $matches[1];
        try {
            $decoded = $this->jwtService->decodeToken($jwtToken);
            $request = $request->withAttribute('user_uuid', $decoded->user_uuid);
        } catch (Throwable $e) {
            $response = ApplicationContext::getContainer()->get(HttpResponse::class);
            return $response->json([
                'error' => true,
                'message' => 'Invalid or expired JWT token.',
            ])->withStatus(401);
        }

        return $handler->handle($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Middleware;

use Hyperf\Context\ApplicationContext;
use Hyperf\Redis\Redis;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        $queryParams = $request->getQueryParams();

        // 1 req/sec for POST, PUT, DELETE and GET with query parameters
        // 3 req/sec for simple GET queries without parameters
        $maxRequests = 3;
        if ($method !== 'GET' || ! empty($queryParams)) {
            $maxRequests = 1;
        }

        $clientIp = $this->getClientIp($request);
        $currentTime = time();
        $rateLimitKey = sprintf('rate_limit:%s:%s:%d', $clientIp, strtolower($method), $currentTime);

        try {
            $redis = ApplicationContext::getContainer()->get(Redis::class);
            $currentCount = (int) $redis->incr($rateLimitKey);

            if ($currentCount === 1) {
                $redis->expire($rateLimitKey, 2);
            }

            if ($currentCount > $maxRequests) {
                /** @var \Hyperf\HttpServer\Contract\ResponseInterface $response */
                $response = ApplicationContext::getContainer()->get(\Hyperf\HttpServer\Contract\ResponseInterface::class);
                return $response->json([
                    'error' => true,
                    'message' => 'Rate limit exceeded. Please wait before making more requests.',
                ])->withStatus(429);
            }
        } catch (\Throwable $e) {
            // If Redis is not running in local test environment, proceed gracefully
        }

        return $handler->handle($request);
    }

    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        return $serverParams['remote_addr'] ?? '127.0.0.1';
    }
}

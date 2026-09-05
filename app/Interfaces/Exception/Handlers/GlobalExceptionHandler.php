<?php

declare(strict_types=1);

namespace App\Interfaces\Exception\Handlers;

use App\Domain\Logging\Entities\LogError;
use App\Domain\Logging\Repositories\LogErrorRepositoryInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GlobalExceptionHandler extends ExceptionHandler
{
    public function __construct(
        protected StdoutLoggerInterface $logger,
        protected LogErrorRepositoryInterface $logErrorRepository
    ) {}

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->stopPropagation();

        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = \Hyperf\Context\Context::get(\Psr\Http\Message\ServerRequestInterface::class);

        $endpoint = $request ? $request->getUri()->getPath() : 'CLI/Job';
        $rawParams = [];
        if ($request) {
            $parsedBody = $request->getParsedBody();
            $queryParams = $request->getQueryParams();
            $rawParams = array_merge(
                is_array($parsedBody) ? $parsedBody : [],
                is_array($queryParams) ? $queryParams : []
            );
        }

        // Sanitize sensitive fields (password, raw passwords)
        $sanitizedParams = $rawParams;
        if (isset($sanitizedParams['password'])) {
            $sanitizedParams['password'] = '******';
        }
        if (isset($sanitizedParams['senha'])) {
            $sanitizedParams['senha'] = '******';
        }

        // Save exception in fb_log_error table
        $logError = new LogError(
            page: $endpoint,
            file: $throwable->getFile(),
            method: $this->extractCallingMethod($throwable),
            line: $throwable->getLine(),
            message: $throwable->getMessage(),
            data: $rawParams,
            dataSanitized: $sanitizedParams !== $rawParams ? $sanitizedParams : null
        );

        try {
            $this->logErrorRepository->save($logError);
        } catch (Throwable $dbError) {
            $this->logger->error('Failed to log error into database: ' . $dbError->getMessage());
        }

        $statusCode = ($throwable instanceof \InvalidArgumentException) ? 400 : 500;

        $bodyData = json_encode([
            'error' => true,
            'message' => $throwable->getMessage(),
            'trace_id' => $logError->getUuid(),
        ]);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode)
            ->withBody(new SwooleStream($bodyData));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    private function extractCallingMethod(Throwable $throwable): string
    {
        $trace = $throwable->getTrace();
        if (isset($trace[0]['class'], $trace[0]['function'])) {
            return $trace[0]['class'] . '::' . $trace[0]['function'];
        }
        if (isset($trace[0]['function'])) {
            return $trace[0]['function'];
        }
        return 'unknown';
    }
}

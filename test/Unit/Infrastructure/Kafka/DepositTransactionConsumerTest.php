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

namespace HyperfTest\Unit\Infrastructure\Kafka;

use App\Application\Handlers\ProcessDepositHandler;
use App\Infrastructure\Kafka\Consumer\DepositTransactionConsumer;
use Hyperf\Contract\StdoutLoggerInterface;
use longlang\phpkafka\Consumer\ConsumeMessage;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class DepositTransactionConsumerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testConsumeProcessesDepositSuccessfully(): void
    {
        $handler = Mockery::mock(ProcessDepositHandler::class);
        $logger = Mockery::mock(StdoutLoggerInterface::class);
        $message = Mockery::mock(ConsumeMessage::class);

        $payload = json_encode([
            'account_number' => '123456789-0',
            'amount' => 150.00,
            'transaction_uuid' => 'tx-uuid-123',
        ]);
        $message->shouldReceive('getValue')->andReturn($payload);

        $handler->shouldReceive('handle')->once()->with('123456789-0', 150.00, 'tx-uuid-123');
        $logger->shouldReceive('info')->twice();

        $consumer = new DepositTransactionConsumer($handler, $logger);
        $consumer->consume($message);

        $this->assertTrue(true);
    }

    public function testConsumeLogsWarningOnInvalidPayload(): void
    {
        $handler = Mockery::mock(ProcessDepositHandler::class);
        $logger = Mockery::mock(StdoutLoggerInterface::class);
        $message = Mockery::mock(ConsumeMessage::class);

        $payload = json_encode([
            'account_number' => '',
            'amount' => 'not-a-number',
        ]);
        $message->shouldReceive('getValue')->andReturn($payload);
        $logger->shouldReceive('warning')->once();
        $handler->shouldNotReceive('handle');

        $consumer = new DepositTransactionConsumer($handler, $logger);
        $consumer->consume($message);

        $this->assertTrue(true);
    }
}

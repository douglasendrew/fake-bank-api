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

use App\Application\Handlers\ProcessPixTransferHandler;
use App\Infrastructure\Kafka\Consumer\PixTransferConsumer;
use Hyperf\Contract\StdoutLoggerInterface;
use longlang\phpkafka\Consumer\ConsumeMessage;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class PixTransferConsumerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testConsumeProcessesPixTransferSuccessfully(): void
    {
        $handler = Mockery::mock(ProcessPixTransferHandler::class);
        $logger = Mockery::mock(StdoutLoggerInterface::class);
        $message = Mockery::mock(ConsumeMessage::class);

        $payload = json_encode([
            'sender_account_id' => 1,
            'destination_account_id' => 2,
            'amount' => 75.00,
            'type' => 'cpf',
            'target_key_or_account' => '52998224725',
            'transaction_uuid' => 'tx-pix-123',
        ]);
        $message->shouldReceive('getValue')->andReturn($payload);

        $handler->shouldReceive('handle')->once()->with(1, 2, 75.00, 'cpf', '52998224725', 'tx-pix-123');
        $logger->shouldReceive('info')->twice();

        $consumer = new PixTransferConsumer($handler, $logger);
        $consumer->consume($message);

        $this->assertTrue(true);
    }

    public function testConsumeLogsWarningOnInvalidPayload(): void
    {
        $handler = Mockery::mock(ProcessPixTransferHandler::class);
        $logger = Mockery::mock(StdoutLoggerInterface::class);
        $message = Mockery::mock(ConsumeMessage::class);

        $payload = json_encode([
            'sender_account_id' => 'invalid',
            'destination_account_id' => 2,
        ]);
        $message->shouldReceive('getValue')->andReturn($payload);
        $logger->shouldReceive('warning')->once();
        $handler->shouldNotReceive('handle');

        $consumer = new PixTransferConsumer($handler, $logger);
        $consumer->consume($message);

        $this->assertTrue(true);
    }
}

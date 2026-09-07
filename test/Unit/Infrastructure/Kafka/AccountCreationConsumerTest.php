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

use App\Application\Handlers\ProcessAccountCreationHandler;
use App\Infrastructure\Kafka\Consumer\AccountCreationConsumer;
use Hyperf\Contract\StdoutLoggerInterface;
use longlang\phpkafka\Consumer\ConsumeMessage;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class AccountCreationConsumerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testConsumeProcessesAccountCreation(): void
    {
        $handler = Mockery::mock(ProcessAccountCreationHandler::class);
        $logger = Mockery::mock(StdoutLoggerInterface::class);
        $message = Mockery::mock(ConsumeMessage::class);

        $payload = json_encode(['user_uuid' => 'user-uuid-123']);
        $message->shouldReceive('getValue')->andReturn($payload);

        $handler->shouldReceive('handle')->once()->with('user-uuid-123');
        $logger->shouldReceive('info')->twice();

        $consumer = new AccountCreationConsumer($handler, $logger);
        $consumer->consume($message);

        $this->assertTrue(true);
    }

    public function testConsumeSkipsEmptyMessage(): void
    {
        $handler = Mockery::mock(ProcessAccountCreationHandler::class);
        $logger = Mockery::mock(StdoutLoggerInterface::class);
        $message = Mockery::mock(ConsumeMessage::class);

        $message->shouldReceive('getValue')->andReturn('');
        $handler->shouldNotReceive('handle');

        $consumer = new AccountCreationConsumer($handler, $logger);
        $consumer->consume($message);

        $this->assertTrue(true);
    }

    public function testConsumeLogsWarningWhenUserUuidMissing(): void
    {
        $handler = Mockery::mock(ProcessAccountCreationHandler::class);
        $logger = Mockery::mock(StdoutLoggerInterface::class);
        $message = Mockery::mock(ConsumeMessage::class);

        $payload = json_encode(['other_field' => 'val']);
        $message->shouldReceive('getValue')->andReturn($payload);
        $logger->shouldReceive('warning')->once();
        $handler->shouldNotReceive('handle');

        $consumer = new AccountCreationConsumer($handler, $logger);
        $consumer->consume($message);

        $this->assertTrue(true);
    }
}

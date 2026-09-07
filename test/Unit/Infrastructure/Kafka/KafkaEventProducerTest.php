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

use App\Infrastructure\Kafka\Producer\KafkaEventProducer;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Kafka\Producer;
use Hyperf\Kafka\ProducerManager;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @internal
 * @coversNothing
 */
class KafkaEventProducerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testPublishSuccessfullySendsMessage(): void
    {
        $producerManager = Mockery::mock(ProducerManager::class);
        $producer = Mockery::mock(Producer::class);
        $logger = Mockery::mock(StdoutLoggerInterface::class);

        $producerManager->shouldReceive('getProducer')->with('default')->andReturn($producer);
        $producer->shouldReceive('send')->once()->with(
            'bank.account.creation',
            json_encode(['user_uuid' => 'test-uuid'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'test-uuid'
        );
        $logger->shouldReceive('info')->once();

        $eventProducer = new KafkaEventProducer($producerManager, $logger);
        $eventProducer->publish('bank.account.creation', ['user_uuid' => 'test-uuid'], 'test-uuid');

        $this->assertTrue(true);
    }

    public function testPublishLogsAndRethrowsOnException(): void
    {
        $producerManager = Mockery::mock(ProducerManager::class);
        $producer = Mockery::mock(Producer::class);
        $logger = Mockery::mock(StdoutLoggerInterface::class);

        $producerManager->shouldReceive('getProducer')->with('default')->andReturn($producer);
        $producer->shouldReceive('send')->once()->andThrow(new RuntimeException('Kafka broker down'));
        $logger->shouldReceive('error')->once();

        $eventProducer = new KafkaEventProducer($producerManager, $logger);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kafka broker down');

        $eventProducer->publish('bank.account.creation', ['user_uuid' => 'test-uuid']);
    }
}

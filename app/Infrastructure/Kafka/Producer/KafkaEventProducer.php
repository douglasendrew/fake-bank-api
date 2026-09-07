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

namespace App\Infrastructure\Kafka\Producer;

use App\Application\Common\Contracts\EventProducerInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Kafka\ProducerManager;
use JsonException;
use Throwable;

use function Hyperf\Support\env;

class KafkaEventProducer implements EventProducerInterface
{
    public function __construct(
        private ProducerManager $producerManager,
        private ?StdoutLoggerInterface $logger = null
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @throws JsonException|Throwable
     */
    public function publish(string $topic, array $payload, ?string $key = null): void
    {
        if (! (bool) env('KAFKA_ENABLE', true)) {
            $this->logger?->warning(sprintf('[Kafka] KAFKA_ENABLE is false. Skipping publishing to topic "%s"', $topic));
            return;
        }

        $messageValue = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        try {
            $producer = $this->producerManager->getProducer('default');
            $producer->send($topic, $messageValue, $key);
            $this->logger?->info(sprintf('[Kafka] Published event to topic "%s" [key: %s]', $topic, $key ?? 'none'));
        } catch (Throwable $e) {
            $this->logger?->error(sprintf('[Kafka] Failed to publish event to topic "%s": %s', $topic, $e->getMessage()));
            throw $e;
        }
    }
}

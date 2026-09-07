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

namespace App\Infrastructure\Kafka\Consumer;

use App\Application\Handlers\ProcessAccountCreationHandler;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Kafka\AbstractConsumer;
use Hyperf\Kafka\Annotation\Consumer;
use longlang\phpkafka\Consumer\ConsumeMessage;
use Throwable;

use function Hyperf\Support\env;

#[Consumer(
    topic: 'bank.account.creation',
    groupId: 'fake_bank_group',
    nums: 1
)]
class AccountCreationConsumer extends AbstractConsumer
{
    public function __construct(
        private ProcessAccountCreationHandler $handler,
        private ?StdoutLoggerInterface $logger = null
    ) {
    }

    public function isEnable(bool $enable): bool
    {
        return parent::isEnable($enable) && (bool) env('KAFKA_ENABLE', true);
    }

    public function consume(ConsumeMessage $message): void
    {
        $payloadRaw = $message->getValue();
        if (empty($payloadRaw)) {
            return;
        }

        try {
            $data = json_decode($payloadRaw, true, 512, JSON_THROW_ON_ERROR);
            $userUuid = $data['user_uuid'] ?? null;

            if (! is_string($userUuid) || empty($userUuid)) {
                $this->logger?->warning('[AccountCreationConsumer] Missing user_uuid in payload: ' . $payloadRaw);
                return;
            }

            $this->logger?->info(sprintf('[AccountCreationConsumer] Processing account creation for user %s', $userUuid));
            $this->handler->handle($userUuid);
            $this->logger?->info(sprintf('[AccountCreationConsumer] Successfully processed account creation for user %s', $userUuid));
        } catch (Throwable $e) {
            $this->logger?->error(sprintf('[AccountCreationConsumer] Error processing message: %s', $e->getMessage()));
            throw $e;
        }
    }
}

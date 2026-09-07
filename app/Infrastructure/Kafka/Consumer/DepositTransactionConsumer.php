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

use App\Application\Handlers\ProcessDepositHandler;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Kafka\AbstractConsumer;
use Hyperf\Kafka\Annotation\Consumer;
use longlang\phpkafka\Consumer\ConsumeMessage;
use Throwable;

use function Hyperf\Support\env;

#[Consumer(
    topic: 'bank.transaction.deposit',
    groupId: 'fake_bank_group',
    nums: 1
)]
class DepositTransactionConsumer extends AbstractConsumer
{
    public function __construct(
        private ProcessDepositHandler $handler,
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
            $accountNumber = $data['account_number'] ?? null;
            $amount = $data['amount'] ?? null;
            $transactionUuid = $data['transaction_uuid'] ?? null;

            if (! is_string($accountNumber) || empty($accountNumber) || ! is_numeric($amount)) {
                $this->logger?->warning('[DepositTransactionConsumer] Invalid deposit payload: ' . $payloadRaw);
                return;
            }

            $this->logger?->info(sprintf(
                '[DepositTransactionConsumer] Processing deposit of %s for account %s [Tx: %s]',
                (string) $amount,
                $accountNumber,
                $transactionUuid ?? 'none'
            ));

            $this->handler->handle($accountNumber, (float) $amount, is_string($transactionUuid) ? $transactionUuid : null);

            $this->logger?->info(sprintf(
                '[DepositTransactionConsumer] Successfully processed deposit for account %s [Tx: %s]',
                $accountNumber,
                $transactionUuid ?? 'none'
            ));
        } catch (Throwable $e) {
            $this->logger?->error(sprintf('[DepositTransactionConsumer] Error processing deposit: %s', $e->getMessage()));
            throw $e;
        }
    }
}

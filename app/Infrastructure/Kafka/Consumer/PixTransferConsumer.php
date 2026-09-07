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

use App\Application\Handlers\ProcessPixTransferHandler;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Kafka\AbstractConsumer;
use Hyperf\Kafka\Annotation\Consumer;
use longlang\phpkafka\Consumer\ConsumeMessage;
use Throwable;

use function Hyperf\Support\env;

#[Consumer(
    topic: 'bank.transaction.pix',
    groupId: 'fake_bank_group',
    nums: 1
)]
class PixTransferConsumer extends AbstractConsumer
{
    public function __construct(
        private ProcessPixTransferHandler $handler,
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

            $senderAccountId = $data['sender_account_id'] ?? null;
            $destinationAccountId = $data['destination_account_id'] ?? null;
            $amount = $data['amount'] ?? null;
            $type = $data['type'] ?? 'pix';
            $targetKeyOrAccount = $data['target_key_or_account'] ?? '';
            $transactionUuid = $data['transaction_uuid'] ?? null;

            if (! is_numeric($senderAccountId) || ! is_numeric($destinationAccountId) || ! is_numeric($amount)) {
                $this->logger?->warning('[PixTransferConsumer] Invalid PIX payload: ' . $payloadRaw);
                return;
            }

            $this->logger?->info(sprintf(
                '[PixTransferConsumer] Processing PIX transfer from account %d to %d of amount %s [Tx: %s]',
                (int) $senderAccountId,
                (int) $destinationAccountId,
                (string) $amount,
                $transactionUuid ?? 'none'
            ));

            $this->handler->handle(
                (int) $senderAccountId,
                (int) $destinationAccountId,
                (float) $amount,
                (string) $type,
                (string) $targetKeyOrAccount,
                is_string($transactionUuid) ? $transactionUuid : null
            );

            $this->logger?->info(sprintf(
                '[PixTransferConsumer] Successfully settled PIX transfer [Tx: %s]',
                $transactionUuid ?? 'none'
            ));
        } catch (Throwable $e) {
            $this->logger?->error(sprintf('[PixTransferConsumer] Error processing PIX transfer: %s', $e->getMessage()));
            throw $e;
        }
    }
}

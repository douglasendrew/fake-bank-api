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

namespace App\Application\UseCases\Pix;

use App\Application\Common\Contracts\EventProducerInterface;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use InvalidArgumentException;

class ConfirmPixTransferUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AccountRepositoryInterface $accountRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private EventProducerInterface $eventProducer
    ) {
    }

    public function execute(string $senderUuid, string $transactionUuid): array
    {
        $senderUser = $this->userRepository->findByUuid($senderUuid);
        if (! $senderUser) {
            throw new InvalidArgumentException('Sender user not found.');
        }

        $senderAccount = $this->accountRepository->findByUserId($senderUser->getId());
        if (! $senderAccount) {
            throw new InvalidArgumentException('Sender account not found.');
        }

        $transaction = $this->transactionRepository->findByUuid($transactionUuid);
        if (! $transaction || $transaction->getType() !== 'pix_transfer') {
            throw new InvalidArgumentException('PIX transaction not found.');
        }

        if ($transaction->getOriginAccountId() !== $senderAccount->getId()) {
            throw new InvalidArgumentException('You are not authorized to confirm this transaction.');
        }

        if ($transaction->getStatus() !== 'created') {
            throw new InvalidArgumentException("Transaction cannot be confirmed. Current status: {$transaction->getStatus()}.");
        }

        // Re-verify funds before dispatching to Kafka
        if ($senderAccount->getBalance()->getAmount() < $transaction->getAmount()->getAmount()) {
            throw new InvalidArgumentException('Insufficient funds for PIX transfer.');
        }

        // Transition status to 'processing'
        $transaction->setStatus('processing');
        $this->transactionRepository->save($transaction);

        // Publish PIX transaction event to Kafka
        $payload = $transaction->getPayload() ?? [];
        $this->eventProducer->publish('bank.transaction.pix', [
            'transaction_uuid' => $transaction->getUuid(),
            'sender_account_id' => $transaction->getOriginAccountId(),
            'destination_account_id' => $transaction->getDestinationAccountId(),
            'amount' => $transaction->getAmount()->getAmount(),
            'type' => $payload['transfer_type'] ?? 'pix',
            'target_key_or_account' => $payload['target_key_or_account'] ?? '',
            'timestamp' => time(),
        ], (string) $transaction->getOriginAccountId());

        return [
            'message' => 'PIX transfer confirmed and sent for processing.',
            'identifier' => $transaction->getUuid(),
            'amount' => $transaction->getAmount()->getAmount(),
            'status' => 'processing',
            'recipient' => $payload['recipient'] ?? null,
        ];
    }
}

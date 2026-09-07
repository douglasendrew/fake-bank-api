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

namespace App\Application\Handlers;

use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\ValueObjects\Money;
use Exception;
use Hyperf\DbConnection\Db;
use Throwable;

class ProcessPixTransferHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private TransactionRepositoryInterface $transactionRepository
    ) {
    }

    public function handle(
        int $senderAccountId,
        int $destinationAccountId,
        float $amount,
        string $type,
        string $targetKeyOrAccount,
        ?string $transactionUuid = null
    ): void {
        $transferAmount = new Money($amount);

        try {
            Db::transaction(function () use ($senderAccountId, $destinationAccountId, $transferAmount, $type, $targetKeyOrAccount, $transactionUuid) {
                $senderAccount = $this->accountRepository->findById($senderAccountId);
                $destinationAccount = $this->accountRepository->findById($destinationAccountId);

                if (! $senderAccount || ! $destinationAccount) {
                    throw new Exception('Invalid accounts for PIX transfer.');
                }

                // Deduct balance from sender
                $senderAccount->withdraw($transferAmount);
                $this->accountRepository->save($senderAccount);

                // Add balance to destination
                $destinationAccount->deposit($transferAmount);
                $this->accountRepository->save($destinationAccount);

                // Update existing transaction or record new transaction
                if ($transactionUuid) {
                    $transaction = $this->transactionRepository->findByUuid($transactionUuid);
                    if ($transaction) {
                        $transaction->setStatus('completed');
                        $this->transactionRepository->save($transaction);
                        return;
                    }
                }

                $transaction = new Transaction(
                    originAccountId: $senderAccount->getId(),
                    destinationAccountId: $destinationAccount->getId(),
                    type: 'pix_transfer',
                    amount: $transferAmount,
                    status: 'completed',
                    payload: [
                        'transfer_type' => $type,
                        'target_key_or_account' => $targetKeyOrAccount,
                    ]
                );

                $this->transactionRepository->save($transaction);
            });
        } catch (Throwable $e) {
            if ($transactionUuid) {
                $transaction = $this->transactionRepository->findByUuid($transactionUuid);
                if ($transaction) {
                    $transaction->setStatus('failed');
                    $this->transactionRepository->save($transaction);
                }
            }
            throw $e;
        }
    }
}

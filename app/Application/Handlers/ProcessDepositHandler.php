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

class ProcessDepositHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private TransactionRepositoryInterface $transactionRepository
    ) {
    }

    public function handle(string $accountNumber, float $amount, ?string $transactionUuid = null): void
    {
        $account = $this->accountRepository->findByAccountNumber($accountNumber);
        if (! $account) {
            if ($transactionUuid) {
                $transaction = $this->transactionRepository->findByUuid($transactionUuid);
                if ($transaction) {
                    $transaction->setStatus('failed');
                    $this->transactionRepository->save($transaction);
                }
            }
            return;
        }

        $depositAmount = new Money($amount);
        $account->deposit($depositAmount);
        $this->accountRepository->save($account);

        // Update existing transaction or create new one if not provided
        if ($transactionUuid) {
            $transaction = $this->transactionRepository->findByUuid($transactionUuid);
            if ($transaction) {
                $transaction->setStatus('completed');
                $this->transactionRepository->save($transaction);
                return;
            }
        }

        // Record transaction ledger entry
        $transaction = new Transaction(
            originAccountId: null,
            destinationAccountId: $account->getId(),
            type: 'deposit',
            amount: $depositAmount,
            status: 'completed'
        );

        $this->transactionRepository->save($transaction);
    }
}

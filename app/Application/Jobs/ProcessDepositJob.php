<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\ValueObjects\Money;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;

class ProcessDepositJob extends Job
{
    public function __construct(
        public string $accountNumber,
        public float $amount
    ) {}

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = $container->get(AccountRepositoryInterface::class);
        /** @var TransactionRepositoryInterface $transactionRepository */
        $transactionRepository = $container->get(TransactionRepositoryInterface::class);

        $account = $accountRepository->findByAccountNumber($this->accountNumber);
        if (! $account) {
            return;
        }

        $depositAmount = new Money($this->amount);
        $account->deposit($depositAmount);
        $accountRepository->save($account);

        // Record transaction ledger entry
        $transaction = new Transaction(
            originAccountId: null,
            destinationAccountId: $account->getId(),
            type: 'deposit',
            amount: $depositAmount,
            status: 'completed'
        );

        $transactionRepository->save($transaction);
    }
}

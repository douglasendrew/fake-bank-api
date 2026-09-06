<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\ValueObjects\Money;
use AllowDynamicProperties;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;

#[AllowDynamicProperties]
class ProcessDepositJob extends Job
{
    public string $accountNumber;

    public float $amount;

    public ?string $transactionUuid = null;

    public function __construct(
        string $accountNumber,
        float $amount,
        ?string $transactionUuid = null
    ) {
        $this->accountNumber = $accountNumber;
        $this->amount = $amount;
        $this->transactionUuid = $transactionUuid;
    }

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = $container->get(AccountRepositoryInterface::class);
        /** @var TransactionRepositoryInterface $transactionRepository */
        $transactionRepository = $container->get(TransactionRepositoryInterface::class);

        $account = $accountRepository->findByAccountNumber($this->accountNumber);
        if (! $account) {
            if ($this->transactionUuid) {
                $transaction = $transactionRepository->findByUuid($this->transactionUuid);
                if ($transaction) {
                    $transaction->setStatus('failed');
                    $transactionRepository->save($transaction);
                }
            }
            return;
        }

        $depositAmount = new Money($this->amount);
        $account->deposit($depositAmount);
        $accountRepository->save($account);

        // Update existing transaction or create new one if not provided
        if ($this->transactionUuid) {
            $transaction = $transactionRepository->findByUuid($this->transactionUuid);
            if ($transaction) {
                $transaction->setStatus('completed');
                $transactionRepository->save($transaction);
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

        $transactionRepository->save($transaction);
    }
}

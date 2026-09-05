<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\ValueObjects\Money;
use Exception;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;

class ProcessPixTransferJob extends Job
{
    public function __construct(
        public int $senderAccountId,
        public int $destinationAccountId,
        public float $amount,
        public string $type,
        public string $targetKeyOrAccount
    ) {}

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = $container->get(AccountRepositoryInterface::class);
        /** @var TransactionRepositoryInterface $transactionRepository */
        $transactionRepository = $container->get(TransactionRepositoryInterface::class);

        $transferAmount = new Money($this->amount);

        Db::transaction(function () use ($accountRepository, $transactionRepository, $transferAmount) {
            $senderAccount = $accountRepository->findById($this->senderAccountId);
            $destinationAccount = $accountRepository->findById($this->destinationAccountId);

            if (! $senderAccount || ! $destinationAccount) {
                throw new Exception('Invalid accounts for PIX transfer.');
            }

            // Deduct balance from sender
            $senderAccount->withdraw($transferAmount);
            $accountRepository->save($senderAccount);

            // Add balance to destination
            $destinationAccount->deposit($transferAmount);
            $accountRepository->save($destinationAccount);

            // Record transaction
            $transaction = new Transaction(
                originAccountId: $senderAccount->getId(),
                destinationAccountId: $destinationAccount->getId(),
                type: 'pix_transfer',
                amount: $transferAmount,
                status: 'completed',
                payload: [
                    'transfer_type' => $this->type,
                    'target_key_or_account' => $this->targetKeyOrAccount,
                ]
            );

            $transactionRepository->save($transaction);
        });
    }
}

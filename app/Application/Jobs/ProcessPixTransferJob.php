<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\ValueObjects\Money;
use AllowDynamicProperties;
use Exception;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;

#[AllowDynamicProperties]
class ProcessPixTransferJob extends Job
{
    public int $senderAccountId;

    public int $destinationAccountId;

    public float $amount;

    public string $type;

    public string $targetKeyOrAccount;

    public ?string $transactionUuid = null;

    public function __construct(
        int $senderAccountId,
        int $destinationAccountId,
        float $amount,
        string $type,
        string $targetKeyOrAccount,
        ?string $transactionUuid = null
    ) {
        $this->senderAccountId = $senderAccountId;
        $this->destinationAccountId = $destinationAccountId;
        $this->amount = $amount;
        $this->type = $type;
        $this->targetKeyOrAccount = $targetKeyOrAccount;
        $this->transactionUuid = $transactionUuid;
    }

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = $container->get(AccountRepositoryInterface::class);
        /** @var TransactionRepositoryInterface $transactionRepository */
        $transactionRepository = $container->get(TransactionRepositoryInterface::class);

        $transferAmount = new Money($this->amount);

        try {
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

                // Update existing transaction or record new transaction
                if ($this->transactionUuid) {
                    $transaction = $transactionRepository->findByUuid($this->transactionUuid);
                    if ($transaction) {
                        $transaction->setStatus('completed');
                        $transactionRepository->save($transaction);
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
                        'transfer_type' => $this->type,
                        'target_key_or_account' => $this->targetKeyOrAccount,
                    ]
                );

                $transactionRepository->save($transaction);
            });
        } catch (\Throwable $e) {
            if ($this->transactionUuid) {
                $transaction = $transactionRepository->findByUuid($this->transactionUuid);
                if ($transaction) {
                    $transaction->setStatus('failed');
                    $transactionRepository->save($transaction);
                }
            }
            throw $e;
        }
    }
}

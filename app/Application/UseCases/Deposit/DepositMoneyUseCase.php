<?php

declare(strict_types=1);

namespace App\Application\UseCases\Deposit;

use App\Application\Jobs\ProcessDepositJob;
use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Money;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use InvalidArgumentException;

class DepositMoneyUseCase
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private DriverFactory $driverFactory,
        private UserRepositoryInterface $userRepository,
        private TransactionRepositoryInterface $transactionRepository
    ) {}

    public function execute(?string $accountNumber, float $amount, ?string $userUuid = null): array
    {
        $money = new Money($amount);

        $account = null;
        if (! empty($accountNumber)) {
            $account = $this->accountRepository->findByAccountNumber($accountNumber);
        } elseif (! empty($userUuid)) {
            $user = $this->userRepository->findByUuid($userUuid);
            if ($user) {
                $account = $this->accountRepository->findByUserId($user->getId());
            }
        }

        if (! $account) {
            throw new InvalidArgumentException('Target account not found.');
        }

        // Record pending transaction
        $transaction = new Transaction(
            originAccountId: null,
            destinationAccountId: $account->getId(),
            type: 'deposit',
            amount: $money,
            status: 'pending'
        );
        $savedTransaction = $this->transactionRepository->save($transaction);

        // Push to async processing queue
        $driver = $this->driverFactory->get('default');
        $driver->push(new ProcessDepositJob(
            accountNumber: $account->getAccountNumber()->getValue(),
            amount: $money->getAmount(),
            transactionUuid: $savedTransaction->getUuid()
        ));

        return [
            'message' => 'Deposit requested and queued for processing successfully.',
            'identifier' => $savedTransaction->getUuid(),
            'account_number' => $account->getAccountNumber()->getValue(),
            'amount' => $money->getAmount(),
            'status' => $savedTransaction->getStatus(),
        ];
    }
}

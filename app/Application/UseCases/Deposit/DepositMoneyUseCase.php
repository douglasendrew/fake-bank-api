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

namespace App\Application\UseCases\Deposit;

use App\Application\Common\Contracts\EventProducerInterface;
use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Money;
use InvalidArgumentException;

class DepositMoneyUseCase
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private EventProducerInterface $eventProducer,
        private UserRepositoryInterface $userRepository,
        private TransactionRepositoryInterface $transactionRepository
    ) {
    }

    public function execute(?string $accountNumber, mixed $amount, ?string $userUuid = null): array
    {
        $money = Money::fromCents($amount);

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

        // Publish deposit transaction event to Kafka
        $this->eventProducer->publish('bank.transaction.deposit', [
            'transaction_uuid' => $savedTransaction->getUuid(),
            'account_number' => $account->getAccountNumber()->getValue(),
            'amount' => $money->getAmount(),
            'timestamp' => time(),
        ], $account->getAccountNumber()->getValue());

        return [
            'message' => 'Deposit requested and queued for processing successfully.',
            'identifier' => $savedTransaction->getUuid(),
            'account_number' => $account->getAccountNumber()->getValue(),
            'amount' => $money->getAmount(),
            'status' => $savedTransaction->getStatus(),
        ];
    }
}

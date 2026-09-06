<?php

declare(strict_types=1);

namespace App\Application\UseCases\Deposit;

use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use InvalidArgumentException;

class GetDepositStatusUseCase
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private AccountRepositoryInterface $accountRepository
    ) {}

    public function execute(string $transactionUuid): array
    {
        $transaction = $this->transactionRepository->findByUuid($transactionUuid);

        if (! $transaction || $transaction->getType() !== 'deposit') {
            throw new InvalidArgumentException('Deposit transaction not found.');
        }

        $account = $this->accountRepository->findById($transaction->getDestinationAccountId());

        return [
            'identifier' => $transaction->getUuid(),
            'type' => $transaction->getType(),
            'account_number' => $account?->getAccountNumber()?->getValue(),
            'amount' => $transaction->getAmount()->getAmount(),
            'status' => $transaction->getStatus(),
            'created_at' => $transaction->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $transaction->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}

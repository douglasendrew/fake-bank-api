<?php

declare(strict_types=1);

namespace App\Domain\Account\Repositories;

use App\Domain\Account\Entities\Transaction;

interface TransactionRepositoryInterface
{
    public function save(Transaction $transaction): Transaction;

    public function findByUuid(string $uuid): ?Transaction;

    /**
     * @return Transaction[]
     */
    public function findAllByAccountId(int $accountId): array;
}

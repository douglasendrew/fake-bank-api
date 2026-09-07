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

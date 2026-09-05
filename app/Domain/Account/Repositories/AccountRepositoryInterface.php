<?php

declare(strict_types=1);

namespace App\Domain\Account\Repositories;

use App\Domain\Account\Entities\Account;

interface AccountRepositoryInterface
{
    public function save(Account $account): Account;

    public function findById(int $id): ?Account;

    public function findByUuid(string $uuid): ?Account;

    public function findByUserId(int $userId): ?Account;

    public function findByAccountNumber(string $accountNumber): ?Account;
}

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

use App\Domain\Account\Entities\Account;

interface AccountRepositoryInterface
{
    public function save(Account $account): Account;

    public function findById(int $id): ?Account;

    public function findByUuid(string $uuid): ?Account;

    public function findByUserId(int $userId): ?Account;

    public function findByAccountNumber(string $accountNumber): ?Account;
}

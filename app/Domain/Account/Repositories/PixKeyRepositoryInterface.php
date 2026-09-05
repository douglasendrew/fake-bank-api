<?php

declare(strict_types=1);

namespace App\Domain\Account\Repositories;

use App\Domain\Account\Entities\PixKey;

interface PixKeyRepositoryInterface
{
    public function save(PixKey $pixKey): PixKey;

    public function findByKey(string $key): ?PixKey;

    public function findByUserId(int $userId): ?PixKey;

    public function delete(PixKey $pixKey): void;
}

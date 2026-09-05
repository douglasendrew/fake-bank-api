<?php

declare(strict_types=1);

namespace App\Domain\Account\Repositories;

use App\Domain\Account\Entities\User;

interface UserRepositoryInterface
{
    public function save(User $user): User;

    public function findById(int $id): ?User;

    public function findByUuid(string $uuid): ?User;

    public function findByCpf(string $cpf): ?User;
}

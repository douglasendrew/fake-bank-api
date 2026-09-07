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

use App\Domain\Account\Entities\PixKey;

interface PixKeyRepositoryInterface
{
    public function save(PixKey $pixKey): PixKey;

    public function findByKey(string $key): ?PixKey;

    public function findByUserId(int $userId): ?PixKey;

    /**
     * @return PixKey[]
     */
    public function findAllByUserId(int $userId): array;

    public function delete(PixKey $pixKey): void;
}

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

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Account\Entities\PixKey;
use App\Domain\Account\Repositories\PixKeyRepositoryInterface;
use DateTimeImmutable;
use Hyperf\DbConnection\Db;

class PixKeyRepository implements PixKeyRepositoryInterface
{
    public function save(PixKey $pixKey): PixKey
    {
        if ($pixKey->getId() === null) {
            $id = Db::table('fb_pix_keys')->insertGetId([
                'pk_uuid' => $pixKey->getUuid(),
                'us_id' => $pixKey->getUserId(),
                'ac_id' => $pixKey->getAccountId(),
                'pk_type' => $pixKey->getType(),
                'pk_key' => $pixKey->getKey(),
                'pk_created_at' => $pixKey->getCreatedAt()->format('Y-m-d H:i:s'),
                'pk_updated_at' => $pixKey->getUpdatedAt()->format('Y-m-d H:i:s'),
                'pk_deleted_at' => $pixKey->getDeletedAt()?->format('Y-m-d H:i:s'),
            ], 'pk_id');

            return new PixKey(
                userId: $pixKey->getUserId(),
                accountId: $pixKey->getAccountId(),
                type: $pixKey->getType(),
                key: $pixKey->getKey(),
                id: (int) $id,
                uuid: $pixKey->getUuid(),
                createdAt: $pixKey->getCreatedAt(),
                updatedAt: $pixKey->getUpdatedAt(),
                deletedAt: $pixKey->getDeletedAt()
            );
        }

        Db::table('fb_pix_keys')->where('pk_id', $pixKey->getId())->update([
            'pk_type' => $pixKey->getType(),
            'pk_key' => $pixKey->getKey(),
            'pk_updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'pk_deleted_at' => $pixKey->getDeletedAt()?->format('Y-m-d H:i:s'),
        ]);

        return $pixKey;
    }

    public function findByKey(string $key): ?PixKey
    {
        $row = Db::table('fb_pix_keys')->where('pk_key', $key)->whereNull('pk_deleted_at')->first();
        return $row ? $this->mapToEntity($row) : null;
    }

    public function findByUserId(int $userId): ?PixKey
    {
        $row = Db::table('fb_pix_keys')->where('us_id', $userId)->whereNull('pk_deleted_at')->first();
        return $row ? $this->mapToEntity($row) : null;
    }

    public function findAllByUserId(int $userId): array
    {
        $rows = Db::table('fb_pix_keys')->where('us_id', $userId)->whereNull('pk_deleted_at')->get();
        $keys = [];
        foreach ($rows as $row) {
            $keys[] = $this->mapToEntity($row);
        }
        return $keys;
    }

    public function delete(PixKey $pixKey): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        Db::table('fb_pix_keys')->where('pk_id', $pixKey->getId())->update([
            'pk_deleted_at' => $now,
        ]);
    }

    private function mapToEntity(object $row): PixKey
    {
        return new PixKey(
            userId: (int) $row->us_id,
            accountId: (int) $row->ac_id,
            type: $row->pk_type,
            key: $row->pk_key,
            id: (int) $row->pk_id,
            uuid: $row->pk_uuid,
            createdAt: new DateTimeImmutable($row->pk_created_at),
            updatedAt: new DateTimeImmutable($row->pk_updated_at),
            deletedAt: $row->pk_deleted_at ? new DateTimeImmutable($row->pk_deleted_at) : null
        );
    }
}

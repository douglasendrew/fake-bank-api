<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Account\Entities\User;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Cpf;
use App\Domain\Account\ValueObjects\FullName;
use App\Domain\Account\ValueObjects\Password;
use DateTimeImmutable;
use Hyperf\DbConnection\Db;

class UserRepository implements UserRepositoryInterface
{
    public function save(User $user): User
    {
        $now = new DateTimeImmutable();

        if ($user->getId() === null) {
            $id = Db::table('fb_users')->insertGetId([
                'us_uuid' => $user->getUuid(),
                'us_name' => $user->getName()->getValue(),
                'us_cpf' => $user->getCpf()->getUnformatted(),
                'us_password' => $user->getPassword()->getHashedValue(),
                'us_status' => $user->getStatus(),
                'us_created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'us_updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s'),
                'us_deleted_at' => $user->getDeletedAt()?->format('Y-m-d H:i:s'),
            ], 'us_id');

            return new User(
                name: $user->getName(),
                cpf: $user->getCpf(),
                password: $user->getPassword(),
                status: $user->getStatus(),
                id: (int) $id,
                uuid: $user->getUuid(),
                createdAt: $user->getCreatedAt(),
                updatedAt: $user->getUpdatedAt(),
                deletedAt: $user->getDeletedAt()
            );
        }

        Db::table('fb_users')->where('us_id', $user->getId())->update([
            'us_name' => $user->getName()->getValue(),
            'us_cpf' => $user->getCpf()->getUnformatted(),
            'us_password' => $user->getPassword()->getHashedValue(),
            'us_status' => $user->getStatus(),
            'us_updated_at' => $now->format('Y-m-d H:i:s'),
            'us_deleted_at' => $user->getDeletedAt()?->format('Y-m-d H:i:s'),
        ]);

        return $user;
    }

    public function findById(int $id): ?User
    {
        $row = Db::table('fb_users')->where('us_id', $id)->whereNull('us_deleted_at')->first();
        return $row ? $this->mapToEntity($row) : null;
    }

    public function findByUuid(string $uuid): ?User
    {
        $row = Db::table('fb_users')->where('us_uuid', $uuid)->whereNull('us_deleted_at')->first();
        return $row ? $this->mapToEntity($row) : null;
    }

    public function findByCpf(string $cpf): ?User
    {
        $cleanCpf = preg_replace('/\D/', '', $cpf);
        $row = Db::table('fb_users')->where('us_cpf', $cleanCpf)->whereNull('us_deleted_at')->first();
        return $row ? $this->mapToEntity($row) : null;
    }

    private function mapToEntity(object $row): User
    {
        return new User(
            name: new FullName($row->us_name),
            cpf: new Cpf($row->us_cpf),
            password: new Password($row->us_password, true),
            status: $row->us_status,
            id: (int) $row->us_id,
            uuid: $row->us_uuid,
            createdAt: new DateTimeImmutable($row->us_created_at),
            updatedAt: new DateTimeImmutable($row->us_updated_at),
            deletedAt: $row->us_deleted_at ? new DateTimeImmutable($row->us_deleted_at) : null
        );
    }
}

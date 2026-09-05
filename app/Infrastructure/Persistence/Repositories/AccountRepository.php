<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Account\Entities\Account;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\ValueObjects\AccountNumber;
use App\Domain\Account\ValueObjects\Money;
use DateTimeImmutable;
use Hyperf\DbConnection\Db;

class AccountRepository implements AccountRepositoryInterface
{
    public function save(Account $account): Account
    {
        $now = new DateTimeImmutable();

        if ($account->getId() === null) {
            $id = Db::table('fb_accounts')->insertGetId([
                'ac_uuid' => $account->getUuid(),
                'us_id' => $account->getUserId(),
                'ac_number' => $account->getAccountNumber()->getValue(),
                'ac_balance' => $account->getBalance()->getAmount(),
                'ac_status' => $account->getStatus(),
                'ac_created_at' => $account->getCreatedAt()->format('Y-m-d H:i:s'),
                'ac_updated_at' => $account->getUpdatedAt()->format('Y-m-d H:i:s'),
                'ac_deleted_at' => $account->getDeletedAt()?->format('Y-m-d H:i:s'),
            ], 'ac_id');

            return new Account(
                userId: $account->getUserId(),
                accountNumber: $account->getAccountNumber(),
                balance: $account->getBalance(),
                status: $account->getStatus(),
                id: (int) $id,
                uuid: $account->getUuid(),
                createdAt: $account->getCreatedAt(),
                updatedAt: $account->getUpdatedAt(),
                deletedAt: $account->getDeletedAt()
            );
        }

        Db::table('fb_accounts')->where('ac_id', $account->getId())->update([
            'ac_balance' => $account->getBalance()->getAmount(),
            'ac_status' => $account->getStatus(),
            'ac_updated_at' => $now->format('Y-m-d H:i:s'),
            'ac_deleted_at' => $account->getDeletedAt()?->format('Y-m-d H:i:s'),
        ]);

        return $account;
    }

    public function findById(int $id): ?Account
    {
        $row = Db::table('fb_accounts')->where('ac_id', $id)->whereNull('ac_deleted_at')->first();
        return $row ? $this->mapToEntity($row) : null;
    }

    public function findByUuid(string $uuid): ?Account
    {
        $row = Db::table('fb_accounts')->where('ac_uuid', $uuid)->whereNull('ac_deleted_at')->first();
        return $row ? $this->mapToEntity($row) : null;
    }

    public function findByUserId(int $userId): ?Account
    {
        $row = Db::table('fb_accounts')->where('us_id', $userId)->whereNull('ac_deleted_at')->first();
        return $row ? $this->mapToEntity($row) : null;
    }

    public function findByAccountNumber(string $accountNumber): ?Account
    {
        $row = Db::table('fb_accounts')->where('ac_number', $accountNumber)->whereNull('ac_deleted_at')->first();
        return $row ? $this->mapToEntity($row) : null;
    }

    private function mapToEntity(object $row): Account
    {
        return new Account(
            userId: (int) $row->us_id,
            accountNumber: new AccountNumber($row->ac_number),
            balance: new Money((float) $row->ac_balance),
            status: $row->ac_status,
            id: (int) $row->ac_id,
            uuid: $row->ac_uuid,
            createdAt: new DateTimeImmutable($row->ac_created_at),
            updatedAt: new DateTimeImmutable($row->ac_updated_at),
            deletedAt: $row->ac_deleted_at ? new DateTimeImmutable($row->ac_deleted_at) : null
        );
    }
}

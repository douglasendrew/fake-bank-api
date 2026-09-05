<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\ValueObjects\Money;
use DateTimeImmutable;
use Hyperf\DbConnection\Db;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function save(Transaction $transaction): Transaction
    {
        if ($transaction->getId() === null) {
            $id = Db::table('fb_transactions')->insertGetId([
                'tr_uuid' => $transaction->getUuid(),
                'ac_id_origin' => $transaction->getOriginAccountId(),
                'ac_id_destination' => $transaction->getDestinationAccountId(),
                'tr_type' => $transaction->getType(),
                'tr_amount' => $transaction->getAmount()->getAmount(),
                'tr_status' => $transaction->getStatus(),
                'tr_payload' => $transaction->getPayload() ? json_encode($transaction->getPayload()) : null,
                'tr_created_at' => $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
                'tr_updated_at' => $transaction->getUpdatedAt()->format('Y-m-d H:i:s'),
                'tr_deleted_at' => $transaction->getDeletedAt()?->format('Y-m-d H:i:s'),
            ], 'tr_id');

            return new Transaction(
                originAccountId: $transaction->getOriginAccountId(),
                destinationAccountId: $transaction->getDestinationAccountId(),
                type: $transaction->getType(),
                amount: $transaction->getAmount(),
                status: $transaction->getStatus(),
                payload: $transaction->getPayload(),
                id: (int) $id,
                uuid: $transaction->getUuid(),
                createdAt: $transaction->getCreatedAt(),
                updatedAt: $transaction->getUpdatedAt(),
                deletedAt: $transaction->getDeletedAt()
            );
        }

        Db::table('fb_transactions')->where('tr_id', $transaction->getId())->update([
            'tr_status' => $transaction->getStatus(),
            'tr_updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'tr_deleted_at' => $transaction->getDeletedAt()?->format('Y-m-d H:i:s'),
        ]);

        return $transaction;
    }

    public function findByUuid(string $uuid): ?Transaction
    {
        $row = Db::table('fb_transactions')->where('tr_uuid', $uuid)->whereNull('tr_deleted_at')->first();
        if (! $row) {
            return null;
        }

        return new Transaction(
            originAccountId: $row->ac_id_origin ? (int) $row->ac_id_origin : null,
            destinationAccountId: (int) $row->ac_id_destination,
            type: $row->tr_type,
            amount: new Money((float) $row->tr_amount),
            status: $row->tr_status,
            payload: $row->tr_payload ? json_decode($row->tr_payload, true) : null,
            id: (int) $row->tr_id,
            uuid: $row->tr_uuid,
            createdAt: new DateTimeImmutable($row->tr_created_at),
            updatedAt: new DateTimeImmutable($row->tr_updated_at),
            deletedAt: $row->tr_deleted_at ? new DateTimeImmutable($row->tr_deleted_at) : null
        );
    }
}

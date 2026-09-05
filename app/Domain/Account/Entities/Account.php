<?php

declare(strict_types=1);

namespace App\Domain\Account\Entities;

use App\Domain\Account\ValueObjects\AccountNumber;
use App\Domain\Account\ValueObjects\Money;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class Account
{
    private ?int $id;

    private string $uuid;

    private int $userId;

    private AccountNumber $accountNumber;

    private Money $balance;

    private string $status; // 'active', 'blocked', 'closed'

    private DateTimeImmutable $createdAt;

    private DateTimeImmutable $updatedAt;

    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        int $userId,
        AccountNumber $accountNumber,
        ?Money $balance = null,
        string $status = 'active',
        ?int $id = null,
        ?string $uuid = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?DateTimeImmutable $deletedAt = null
    ) {
        $this->id = $id;
        $this->uuid = $uuid ?? Uuid::uuid4()->toString();
        $this->userId = $userId;
        $this->accountNumber = $accountNumber;
        $this->balance = $balance ?? new Money(0.00);
        $this->status = $status;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        $this->deletedAt = $deletedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAccountNumber(): AccountNumber
    {
        return $this->accountNumber;
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function deposit(Money $amount): void
    {
        $this->balance = $this->balance->add($amount);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function withdraw(Money $amount): void
    {
        $this->balance = $this->balance->subtract($amount);
        $this->updatedAt = new DateTimeImmutable();
    }
}

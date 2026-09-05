<?php

declare(strict_types=1);

namespace App\Domain\Account\Entities;

use App\Domain\Account\ValueObjects\Money;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class Transaction
{
    private ?int $id;

    private string $uuid;

    private ?int $originAccountId;

    private int $destinationAccountId;

    private string $type; // 'deposit', 'pix_transfer'

    private Money $amount;

    private string $status; // 'pending', 'completed', 'failed'

    private ?array $payload;

    private DateTimeImmutable $createdAt;

    private DateTimeImmutable $updatedAt;

    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        ?int $originAccountId,
        int $destinationAccountId,
        string $type,
        Money $amount,
        string $status = 'pending',
        ?array $payload = null,
        ?int $id = null,
        ?string $uuid = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?DateTimeImmutable $deletedAt = null
    ) {
        $this->id = $id;
        $this->uuid = $uuid ?? Uuid::uuid4()->toString();
        $this->originAccountId = $originAccountId;
        $this->destinationAccountId = $destinationAccountId;
        $this->type = $type;
        $this->amount = $amount;
        $this->status = $status;
        $this->payload = $payload;
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

    public function getOriginAccountId(): ?int
    {
        return $this->originAccountId;
    }

    public function getDestinationAccountId(): int
    {
        return $this->destinationAccountId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getPayload(): ?array
    {
        return $this->payload;
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
}

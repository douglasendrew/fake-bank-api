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

namespace App\Domain\Account\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

class PixKey
{
    private ?int $id;

    private string $uuid;

    private int $userId;

    private int $accountId;

    private string $type; // 'cpf', 'email'

    private string $key;

    private DateTimeImmutable $createdAt;

    private DateTimeImmutable $updatedAt;

    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        int $userId,
        int $accountId,
        string $type,
        string $key,
        ?int $id = null,
        ?string $uuid = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?DateTimeImmutable $deletedAt = null
    ) {
        $type = strtolower($type);
        if (! in_array($type, ['cpf', 'email'], true)) {
            throw new InvalidArgumentException("Invalid PIX key type: {$type}. Allowed types: 'cpf', 'email'.");
        }

        if ($type === 'email' && ! filter_var($key, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address for PIX key.');
        }

        $this->id = $id;
        $this->uuid = $uuid ?? Uuid::uuid4()->toString();
        $this->userId = $userId;
        $this->accountId = $accountId;
        $this->type = $type;
        $this->key = $key;
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

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getKey(): string
    {
        return $this->key;
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

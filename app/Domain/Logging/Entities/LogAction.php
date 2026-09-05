<?php

declare(strict_types=1);

namespace App\Domain\Logging\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class LogAction
{
    private ?int $id;

    private string $uuid;

    private ?int $userId;

    private ?int $errorId;

    private string $action;

    private array $actionMetadata;

    private bool $error;

    private DateTimeImmutable $createdAt;

    public function __construct(
        string $action,
        array $actionMetadata,
        bool $error = false,
        ?int $userId = null,
        ?int $errorId = null,
        ?int $id = null,
        ?string $uuid = null,
        ?DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->uuid = $uuid ?? Uuid::uuid4()->toString();
        $this->userId = $userId;
        $this->errorId = $errorId;
        $this->action = $action;
        $this->actionMetadata = $actionMetadata;
        $this->error = $error;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getErrorId(): ?int
    {
        return $this->errorId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getActionMetadata(): array
    {
        return $this->actionMetadata;
    }

    public function isError(): bool
    {
        return $this->error;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

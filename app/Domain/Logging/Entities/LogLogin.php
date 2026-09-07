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

namespace App\Domain\Logging\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class LogLogin
{
    private ?int $id;

    private string $uuid;

    private ?int $userId;

    private string $credential;

    private bool $loginSuccessfully;

    private DateTimeImmutable $createdAt;

    public function __construct(
        string $credential,
        bool $loginSuccessfully,
        ?int $userId = null,
        ?int $id = null,
        ?string $uuid = null,
        ?DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->uuid = $uuid ?? Uuid::uuid4()->toString();
        $this->userId = $userId;
        $this->credential = $credential;
        $this->loginSuccessfully = $loginSuccessfully;
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

    public function getCredential(): string
    {
        return $this->credential;
    }

    public function isLoginSuccessfully(): bool
    {
        return $this->loginSuccessfully;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

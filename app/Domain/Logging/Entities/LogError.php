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

class LogError
{
    private ?int $id;

    private string $uuid;

    private string $page;

    private string $file;

    private string $method;

    private int $line;

    private string $message;

    private array $data;

    private ?array $dataSanitized;

    private DateTimeImmutable $dateError;

    public function __construct(
        string $page,
        string $file,
        string $method,
        int $line,
        string $message,
        array $data,
        ?array $dataSanitized = null,
        ?int $id = null,
        ?string $uuid = null,
        ?DateTimeImmutable $dateError = null
    ) {
        $this->id = $id;
        $this->uuid = $uuid ?? Uuid::uuid4()->toString();
        $this->page = $page;
        $this->file = $file;
        $this->method = $method;
        $this->line = $line;
        $this->message = $message;
        $this->data = $data;
        $this->dataSanitized = $dataSanitized;
        $this->dateError = $dateError ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getPage(): string
    {
        return $this->page;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getDataSanitized(): ?array
    {
        return $this->dataSanitized;
    }

    public function getDateError(): DateTimeImmutable
    {
        return $this->dateError;
    }
}

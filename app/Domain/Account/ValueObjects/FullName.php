<?php

declare(strict_types=1);

namespace App\Domain\Account\ValueObjects;

use InvalidArgumentException;

class FullName
{
    private string $value;

    public function __construct(string $name)
    {
        $trimmedName = trim(preg_replace('/\s+/', ' ', $name));

        $words = explode(' ', $trimmedName);
        if (count($words) < 2) {
            throw new InvalidArgumentException('Full name must include both a first name and at least one surname.');
        }

        // Allow Brazilian accent characters, letters, spaces, apostrophes and hyphens
        if (! preg_match('/^[a-zA-ZÀ-ÿ\s\'-]+$/u', $trimmedName)) {
            throw new InvalidArgumentException('Full name contains invalid characters.');
        }

        $this->value = $trimmedName;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

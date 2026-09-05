<?php

declare(strict_types=1);

namespace App\Domain\Account\ValueObjects;

use InvalidArgumentException;

class Money
{
    private float $amount;

    public function __construct(float $amount)
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }

        $this->amount = round($amount, 2);
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function add(Money $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function subtract(Money $other): self
    {
        if ($other->amount > $this->amount) {
            throw new InvalidArgumentException('Insufficient funds.');
        }

        return new self($this->amount - $other->amount);
    }

    public function __toString(): string
    {
        return number_format($this->amount, 2, '.', '');
    }
}

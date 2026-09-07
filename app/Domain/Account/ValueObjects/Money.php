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

    public function __toString(): string
    {
        return number_format($this->amount, 2, '.', '');
    }

    public static function fromCents(mixed $cents): self
    {
        if (is_float($cents)) {
            throw new InvalidArgumentException('The amount must be an integer in cents.');
        }

        if (! is_int($cents) && ! (is_string($cents) && ctype_digit(ltrim($cents, '+')))) {
            throw new InvalidArgumentException('The amount must be an integer in cents.');
        }

        $intCents = (int) $cents;
        if ($intCents <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return new self($intCents / 100.0);
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function toCents(): int
    {
        return (int) round($this->amount * 100);
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
}

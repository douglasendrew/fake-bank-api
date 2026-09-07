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

class AccountNumber
{
    private string $value;

    public function __construct(string $accountNumber)
    {
        if (! preg_match('/^\d{9}-\d{1}$/', $accountNumber)) {
            throw new InvalidArgumentException('Account number must be in format XXXXXXXXX-X (9 digits + hyphen + 1 digit).');
        }

        $this->value = $accountNumber;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function generate(): self
    {
        $mainDigits = str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        $checkDigit = random_int(0, 9);
        return new self($mainDigits . '-' . $checkDigit);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

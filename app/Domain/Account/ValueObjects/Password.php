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

class Password
{
    private string $hashedValue;

    public function __construct(string $rawPassword, bool $isHashed = false)
    {
        if ($isHashed) {
            $this->hashedValue = $rawPassword;
            return;
        }

        if (! preg_match('/^\d{6}$/', $rawPassword)) {
            throw new InvalidArgumentException('Password must consist of exactly 6 numeric digits.');
        }

        if (preg_match('/^(\d)\1{5}$/', $rawPassword)) {
            throw new InvalidArgumentException('Password cannot consist of repeated digits.');
        }

        if ($this->isSequential($rawPassword)) {
            throw new InvalidArgumentException('Password cannot contain sequential numbers.');
        }

        $this->hashedValue = password_hash($rawPassword, PASSWORD_BCRYPT);
    }

    public function getHashedValue(): string
    {
        return $this->hashedValue;
    }

    public function verify(string $rawPassword): bool
    {
        return password_verify($rawPassword, $this->hashedValue);
    }

    private function isSequential(string $password): bool
    {
        // 1. Check direct 6-digit ascending or descending sequence
        $ascendingSequences = ['012345', '123456', '234567', '345678', '456789', '567890', '678901', '789012', '890123', '901234'];
        $descendingSequences = ['543210', '654321', '765432', '876543', '987654', '098765', '109876', '210987', '321098', '432109'];

        foreach ($ascendingSequences as $seq) {
            if ($password === $seq) {
                return true;
            }
        }

        foreach ($descendingSequences as $seq) {
            if ($password === $seq) {
                return true;
            }
        }

        // 2. Check for 3-digit or 4-digit sequential substrings like '123', '321', '890', etc.
        for ($i = 0; $i <= strlen($password) - 3; ++$i) {
            $d1 = (int) $password[$i];
            $d2 = (int) $password[$i + 1];
            $d3 = (int) $password[$i + 2];

            // Ascending (e.g., 1-2-3 or 8-9-0 wrap)
            if (($d2 === ($d1 + 1) % 10) && ($d3 === ($d2 + 1) % 10)) {
                return true;
            }
            // Descending (e.g., 3-2-1 or 0-9-8 wrap)
            if (($d2 === ($d1 + 9) % 10) && ($d3 === ($d2 + 9) % 10)) {
                return true;
            }
        }

        return false;
    }
}

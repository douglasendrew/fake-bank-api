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

class Cpf
{
    private string $value;

    public function __construct(string $cpf)
    {
        $cleanCpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cleanCpf) !== 11) {
            throw new InvalidArgumentException('CPF must contain exactly 11 numeric digits.');
        }

        if (preg_match('/^(\d)\1{10}$/', $cleanCpf)) {
            throw new InvalidArgumentException('CPF cannot consist of repeated digits.');
        }

        if (! $this->validateCheckDigits($cleanCpf)) {
            throw new InvalidArgumentException('Invalid CPF check digits.');
        }

        $this->value = $cleanCpf;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getUnformatted(): string
    {
        return $this->value;
    }

    public function getFormatted(): string
    {
        return sprintf(
            '%s.%s.%s-%s',
            substr($this->value, 0, 3),
            substr($this->value, 3, 3),
            substr($this->value, 6, 3),
            substr($this->value, 9, 2)
        );
    }

    public function getMasked(): string
    {
        return sprintf(
            '%s.***.***-%s',
            substr($this->value, 0, 3),
            substr($this->value, 9, 2)
        );
    }

    public function equals(Cpf $other): bool
    {
        return $this->value === $other->value;
    }

    private function validateCheckDigits(string $cpf): bool
    {
        for ($t = 9; $t < 11; ++$t) {
            $d = 0;
            for ($c = 0; $c < $t; ++$c) {
                $d += (int) $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ((int) $cpf[$t] !== $d) {
                return false;
            }
        }

        return true;
    }
}

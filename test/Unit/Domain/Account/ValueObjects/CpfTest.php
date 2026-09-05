<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain\Account\ValueObjects;

use App\Domain\Account\ValueObjects\Cpf;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CpfTest extends TestCase
{
    public function testValidUnformattedCpf(): void
    {
        // A standard valid CPF
        $validCpf = '52998224725';
        $cpf = new Cpf($validCpf);

        $this->assertEquals('52998224725', $cpf->getUnformatted());
        $this->assertEquals('529.***.***-25', $cpf->getMasked());
    }

    public function testValidFormattedCpf(): void
    {
        $validCpf = '529.982.247-25';
        $cpf = new Cpf($validCpf);

        $this->assertEquals('52998224725', $cpf->getUnformatted());
        $this->assertEquals('529.***.***-25', $cpf->getMasked());
    }

    public function testInvalidCpfLengthThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Cpf('123456789');
    }

    public function testRepeatedDigitsCpfThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Cpf('11111111111');
    }

    public function testInvalidCheckDigitsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Cpf('12345678901');
    }
}

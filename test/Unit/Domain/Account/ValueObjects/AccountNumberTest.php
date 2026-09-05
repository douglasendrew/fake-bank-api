<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain\Account\ValueObjects;

use App\Domain\Account\ValueObjects\AccountNumber;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AccountNumberTest extends TestCase
{
    public function testGenerateAccountNumber(): void
    {
        $accountNumber = AccountNumber::generate();
        $this->assertMatchesRegularExpression('/^\d{9}-\d{1}$/', $accountNumber->getValue());
    }

    public function testValidExplicitAccountNumber(): void
    {
        $accountNumber = new AccountNumber('123456789-0');
        $this->assertEquals('123456789-0', $accountNumber->getValue());
    }

    public function testInvalidFormatThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AccountNumber('12345-0');
    }
}

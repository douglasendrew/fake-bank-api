<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain\Account\ValueObjects;

use App\Domain\Account\ValueObjects\FullName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FullNameTest extends TestCase
{
    public function testValidFullName(): void
    {
        $fullName = new FullName('Douglas Silva');
        $this->assertEquals('Douglas Silva', $fullName->getValue());
    }

    public function testSingleWordNameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FullName('Douglas');
    }

    public function testNameWithNumbersThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FullName('Douglas Silva123');
    }

    public function testNameWithIrregularCharsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FullName('Douglas @Silva!');
    }
}

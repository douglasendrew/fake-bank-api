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

namespace HyperfTest\Unit\Domain\Account\ValueObjects;

use App\Domain\Account\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class MoneyTest extends TestCase
{
    public function testFromCentsConvertsProperly(): void
    {
        $money10 = Money::fromCents(1000);
        $this->assertEquals(10.00, $money10->getAmount());
        $this->assertEquals(1000, $money10->toCents());
        $this->assertEquals('10.00', (string) $money10);

        $money123 = Money::fromCents(12300);
        $this->assertEquals(123.00, $money123->getAmount());
        $this->assertEquals(12300, $money123->toCents());
        $this->assertEquals('123.00', (string) $money123);

        $moneyCentsString = Money::fromCents('500');
        $this->assertEquals(5.00, $moneyCentsString->getAmount());
        $this->assertEquals(500, $moneyCentsString->toCents());
    }

    public function testFromCentsRejectsFloats(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The amount must be an integer in cents.');

        Money::fromCents(10.50);
    }

    public function testFromCentsRejectsStringFloats(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The amount must be an integer in cents.');

        Money::fromCents('10.50');
    }

    public function testFromCentsRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be greater than zero.');

        Money::fromCents(0);
    }

    public function testFromCentsRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be greater than zero.');

        Money::fromCents(-100);
    }

    public function testAddAndSubtract(): void
    {
        $m1 = Money::fromCents(1000);
        $m2 = Money::fromCents(500);

        $sum = $m1->add($m2);
        $this->assertEquals(15.00, $sum->getAmount());
        $this->assertEquals(1500, $sum->toCents());

        $diff = $m1->subtract($m2);
        $this->assertEquals(5.00, $diff->getAmount());
        $this->assertEquals(500, $diff->toCents());
    }
}

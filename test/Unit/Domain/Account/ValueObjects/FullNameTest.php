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

use App\Domain\Account\ValueObjects\FullName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
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

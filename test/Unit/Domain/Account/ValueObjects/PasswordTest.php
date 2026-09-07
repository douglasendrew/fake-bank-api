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

use App\Domain\Account\ValueObjects\Password;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class PasswordTest extends TestCase
{
    public function testValid6DigitPassword(): void
    {
        $validPassword = '941825';
        $pwd = new Password($validPassword);

        $this->assertTrue($pwd->verify('941825'));
        $this->assertFalse($pwd->verify('123456'));
    }

    public function testInvalidLengthThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Password('12345');
    }

    public function testNonNumericPasswordThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Password('a1b2c3');
    }

    public function testAscendingSequentialPasswordThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Password('123456');
    }

    public function testDescendingSequentialPasswordThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Password('654321');
    }

    public function testRepeatedDigitsPasswordThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Password('222222');
    }

    public function testSequentialSubstringsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Contains "123" or "321" or "890"
        new Password('912385');
    }
}

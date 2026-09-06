<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Application\UseCases\Pix;

use App\Application\UseCases\Pix\ListPixKeysUseCase;
use App\Domain\Account\Entities\PixKey;
use App\Domain\Account\Entities\User;
use App\Domain\Account\Repositories\PixKeyRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Cpf;
use App\Domain\Account\ValueObjects\FullName;
use App\Domain\Account\ValueObjects\Password;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

class ListPixKeysUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testListPixKeysSuccess(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $pixKeyRepository = Mockery::mock(PixKeyRepositoryInterface::class);

        $user = new User(
            name: new FullName('Test User'),
            cpf: new Cpf('52998224725'),
            password: new Password('941825'),
            id: 1
        );

        $userRepository->shouldReceive('findByUuid')->with('user-uuid-123')->andReturn($user);

        $key1 = new PixKey(userId: 1, accountId: 1, type: 'cpf', key: '52998224725');
        $key2 = new PixKey(userId: 1, accountId: 1, type: 'email', key: 'user@test.com');

        $pixKeyRepository->shouldReceive('findAllByUserId')->with(1)->andReturn([$key1, $key2]);

        $useCase = new ListPixKeysUseCase($userRepository, $pixKeyRepository);
        $result = $useCase->execute('user-uuid-123');

        $this->assertCount(2, $result);
        $this->assertEquals('cpf', $result[0]['type']);
        $this->assertEquals('52998224725', $result[0]['key']);
        $this->assertEquals('email', $result[1]['type']);
        $this->assertEquals('user@test.com', $result[1]['key']);
    }

    public function testListPixKeysThrowsWhenUserNotFound(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $pixKeyRepository = Mockery::mock(PixKeyRepositoryInterface::class);

        $userRepository->shouldReceive('findByUuid')->with('invalid-uuid')->andReturn(null);

        $useCase = new ListPixKeysUseCase($userRepository, $pixKeyRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User not found.');

        $useCase->execute('invalid-uuid');
    }
}

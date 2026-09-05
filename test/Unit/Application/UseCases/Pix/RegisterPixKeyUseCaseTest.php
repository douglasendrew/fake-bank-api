<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Application\UseCases\Pix;

use App\Application\UseCases\Pix\RegisterPixKeyUseCase;
use App\Domain\Account\Entities\Account;
use App\Domain\Account\Entities\PixKey;
use App\Domain\Account\Entities\User;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\PixKeyRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

class RegisterPixKeyUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteSuccessRegisteringEmailPixKey(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $pixKeyRepository = Mockery::mock(PixKeyRepositoryInterface::class);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getId')->andReturn(1);

        $account = Mockery::mock(Account::class);
        $account->shouldReceive('getId')->andReturn(10);

        $userRepository->shouldReceive('findByUuid')->with('user-uuid-123')->andReturn($user);
        $accountRepository->shouldReceive('findByUserId')->with(1)->andReturn($account);

        $pixKeyRepository->shouldReceive('findByUserId')->with(1)->andReturn(null);
        $pixKeyRepository->shouldReceive('findByKey')->with('douglas@example.com')->andReturn(null);

        $pixKeyRepository->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (PixKey $key) {
                return $key;
            });

        $useCase = new RegisterPixKeyUseCase($userRepository, $accountRepository, $pixKeyRepository);

        $result = $useCase->execute('user-uuid-123', 'email', 'douglas@example.com');

        $this->assertEquals('email', $result['type']);
        $this->assertEquals('douglas@example.com', $result['key']);
    }

    public function testMaxOnePixKeyLimitThrowsException(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $pixKeyRepository = Mockery::mock(PixKeyRepositoryInterface::class);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getId')->andReturn(1);

        $account = Mockery::mock(Account::class);
        $account->shouldReceive('getId')->andReturn(10);

        $existingKey = Mockery::mock(PixKey::class);

        $userRepository->shouldReceive('findByUuid')->with('user-uuid-123')->andReturn($user);
        $accountRepository->shouldReceive('findByUserId')->with(1)->andReturn($account);
        $pixKeyRepository->shouldReceive('findByUserId')->with(1)->andReturn($existingKey);

        $useCase = new RegisterPixKeyUseCase($userRepository, $accountRepository, $pixKeyRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User already has a registered PIX key.');

        $useCase->execute('user-uuid-123', 'email', 'douglas@example.com');
    }
}

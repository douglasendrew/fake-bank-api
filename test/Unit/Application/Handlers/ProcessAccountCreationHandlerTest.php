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

namespace HyperfTest\Unit\Application\Handlers;

use App\Application\Handlers\ProcessAccountCreationHandler;
use App\Domain\Account\Entities\Account;
use App\Domain\Account\Entities\User;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Cpf;
use App\Domain\Account\ValueObjects\FullName;
use App\Domain\Account\ValueObjects\Password;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ProcessAccountCreationHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testHandleCreatesAccountAndApprovesUser(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);

        $user = new User(
            name: new FullName('Douglas Test'),
            cpf: new Cpf('52998224725'),
            password: new Password('941825'),
            status: 'pending_creation',
            id: 1
        );

        $userRepository->shouldReceive('findByUuid')->with($user->getUuid())->andReturn($user);
        $accountRepository->shouldReceive('findByAccountNumber')->andReturn(null);
        $accountRepository->shouldReceive('save')->once()->with(Mockery::type(Account::class));
        $userRepository->shouldReceive('save')->once()->with(Mockery::on(function (User $savedUser) {
            return $savedUser->getStatus() === 'approved';
        }));

        $handler = new ProcessAccountCreationHandler($userRepository, $accountRepository);
        $handler->handle($user->getUuid());

        $this->assertEquals('approved', $user->getStatus());
    }

    public function testHandleReturnsEarlyWhenUserNotFound(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);

        $userRepository->shouldReceive('findByUuid')->with('unknown-uuid')->andReturn(null);
        $accountRepository->shouldNotReceive('save');
        $userRepository->shouldNotReceive('save');

        $handler = new ProcessAccountCreationHandler($userRepository, $accountRepository);
        $handler->handle('unknown-uuid');

        $this->assertTrue(true);
    }
}

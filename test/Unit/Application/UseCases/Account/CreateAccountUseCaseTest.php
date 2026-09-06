<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Application\UseCases\Account;

use App\Application\UseCases\Account\CreateAccountUseCase;
use App\Domain\Account\Entities\User;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateAccountUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteSuccessfulAccountCreation(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $driverFactory = Mockery::mock(DriverFactory::class);
        $queueDriver = Mockery::mock(DriverInterface::class);

        $driverFactory->shouldReceive('get')->with('default')->andReturn($queueDriver);
        $queueDriver->shouldReceive('push')->once()->andReturn(true);

        $userRepository->shouldReceive('findByCpf')->with('52998224725')->andReturn(null);
        $userRepository->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (User $user) {
                return $user;
            });

        $useCase = new CreateAccountUseCase($userRepository, $driverFactory);

        $result = $useCase->execute('Douglas Silva', '52998224725', '941825');

        $this->assertEquals('pending_creation', $result['status']);
        $this->assertNotEmpty($result['identifier']);
    }

    public function testExecuteDuplicateCpfThrowsException(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $driverFactory = Mockery::mock(DriverFactory::class);

        $existingUser = Mockery::mock(User::class);
        $userRepository->shouldReceive('findByCpf')->with('52998224725')->andReturn($existingUser);

        $useCase = new CreateAccountUseCase($userRepository, $driverFactory);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A user with this CPF already exists.');

        $useCase->execute('Douglas Silva', '52998224725', '941825');
    }
}

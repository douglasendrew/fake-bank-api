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

namespace HyperfTest\Unit\Application\UseCases\Account;

use App\Application\Common\Contracts\EventProducerInterface;
use App\Application\UseCases\Account\CreateAccountUseCase;
use App\Domain\Account\Entities\User;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class CreateAccountUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteSuccessfulAccountCreation(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $eventProducer = Mockery::mock(EventProducerInterface::class);

        $eventProducer->shouldReceive('publish')
            ->once()
            ->with('bank.account.creation', Mockery::type('array'), Mockery::type('string'));

        $userRepository->shouldReceive('findByCpf')->with('52998224725')->andReturn(null);
        $userRepository->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (User $user) {
                return $user;
            });

        $useCase = new CreateAccountUseCase($userRepository, $eventProducer);

        $result = $useCase->execute('Douglas Silva', '52998224725', '941825');

        $this->assertEquals('pending_creation', $result['status']);
        $this->assertNotEmpty($result['identifier']);
    }

    public function testExecuteDuplicateCpfThrowsException(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $eventProducer = Mockery::mock(EventProducerInterface::class);

        $existingUser = Mockery::mock(User::class);
        $userRepository->shouldReceive('findByCpf')->with('52998224725')->andReturn($existingUser);

        $useCase = new CreateAccountUseCase($userRepository, $eventProducer);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A user with this CPF already exists.');

        $useCase->execute('Douglas Silva', '52998224725', '941825');
    }
}

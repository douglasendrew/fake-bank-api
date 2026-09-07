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
use App\Application\UseCases\Account\UpdateAccountUseCase;
use App\Domain\Account\Entities\User;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Cpf;
use App\Domain\Account\ValueObjects\FullName;
use App\Domain\Account\ValueObjects\Password;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class UpdateAccountUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteSuccessfulUpdate(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $eventProducer = Mockery::mock(EventProducerInterface::class);

        $user = new User(
            name: new FullName('Original Name'),
            cpf: new Cpf('52998224725'),
            password: new Password('941825'),
            status: 'approved'
        );

        $userRepository->shouldReceive('findByUuid')->with($user->getUuid())->andReturn($user);
        $userRepository->shouldReceive('findByCpf')->with('52998224725')->andReturn($user);
        $userRepository->shouldReceive('save')->once()->andReturn($user);

        $eventProducer->shouldReceive('publish')
            ->once()
            ->with('bank.account.creation', Mockery::type('array'), $user->getUuid());

        $useCase = new UpdateAccountUseCase($userRepository, $eventProducer);
        $result = $useCase->execute($user->getUuid(), 'New Name', '52998224725', '941825');

        $this->assertEquals('New Name', $result['name']);
        $this->assertEquals($user->getUuid(), $result['identifier']);
    }

    public function testExecuteThrowsWhenUserNotFound(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $eventProducer = Mockery::mock(EventProducerInterface::class);

        $userRepository->shouldReceive('findByUuid')->with('non-existent')->andReturn(null);

        $useCase = new UpdateAccountUseCase($userRepository, $eventProducer);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account not found.');

        $useCase->execute('non-existent', 'Name', '52998224725', '123456');
    }
}

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

namespace HyperfTest\Unit\Application\UseCases\Auth;

use App\Application\UseCases\Auth\AuthenticateUserUseCase;
use App\Domain\Account\Entities\User;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Cpf;
use App\Domain\Account\ValueObjects\FullName;
use App\Domain\Account\ValueObjects\Password;
use App\Domain\Logging\Repositories\LogLoginRepositoryInterface;
use App\Infrastructure\Security\JwtService;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class AuthenticateUserUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSuccessfulAuthenticationReturnsJwtToken(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $logLoginRepository = Mockery::mock(LogLoginRepositoryInterface::class);
        $jwtService = Mockery::mock(JwtService::class);

        $user = new User(
            name: new FullName('Douglas Silva'),
            cpf: new Cpf('52998224725'),
            password: new Password('941825'),
            id: 1
        );

        $userRepository->shouldReceive('findByCpf')->with('52998224725')->andReturn($user);
        $logLoginRepository->shouldReceive('save')->once();
        $jwtService->shouldReceive('generateToken')->with($user->getUuid())->andReturn('jwt-token-123');

        $useCase = new AuthenticateUserUseCase($userRepository, $logLoginRepository, $jwtService);

        $result = $useCase->execute('529.982.247-25', '941825');

        $this->assertEquals('jwt-token-123', $result['token']);
        $this->assertEquals(900, $result['expires_in']);
        $this->assertEquals($user->getUuid(), $result['user']['identifier']);
    }

    public function testFailedAuthenticationThrowsException(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $logLoginRepository = Mockery::mock(LogLoginRepositoryInterface::class);
        $jwtService = Mockery::mock(JwtService::class);

        $userRepository->shouldReceive('findByCpf')->with('52998224725')->andReturn(null);
        $logLoginRepository->shouldReceive('save')->once();

        $useCase = new AuthenticateUserUseCase($userRepository, $logLoginRepository, $jwtService);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CPF or password.');

        $useCase->execute('529.982.247-25', '111111');
    }
}

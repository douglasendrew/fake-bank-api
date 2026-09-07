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

namespace HyperfTest\Unit\Application\UseCases\Deposit;

use App\Application\Common\Contracts\EventProducerInterface;
use App\Application\UseCases\Deposit\DepositMoneyUseCase;
use App\Domain\Account\Entities\Account;
use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Entities\User;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\AccountNumber;
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
class DepositMoneyUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteDepositWithAccountNumber(): void
    {
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);
        $eventProducer = Mockery::mock(EventProducerInterface::class);

        $eventProducer->shouldReceive('publish')
            ->once()
            ->with('bank.transaction.deposit', Mockery::type('array'), '123456789-0');

        $account = new Account(userId: 1, accountNumber: new AccountNumber('123456789-0'), id: 1);
        $accountRepository->shouldReceive('findByAccountNumber')->with('123456789-0')->andReturn($account);
        $transactionRepository->shouldReceive('save')->once()->andReturnUsing(function (Transaction $tx) {
            return $tx;
        });

        $useCase = new DepositMoneyUseCase($accountRepository, $eventProducer, $userRepository, $transactionRepository);

        $result = $useCase->execute('123456789-0', 15000);

        $this->assertEquals('123456789-0', $result['account_number']);
        $this->assertEquals(150.00, $result['amount']);
        $this->assertEquals('pending', $result['status']);
        $this->assertNotEmpty($result['identifier']);
    }

    public function testExecuteDepositUsingAuthenticatedUserUuid(): void
    {
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);
        $eventProducer = Mockery::mock(EventProducerInterface::class);

        $eventProducer->shouldReceive('publish')
            ->once()
            ->with('bank.transaction.deposit', Mockery::type('array'), '987654321-0');

        $user = new User(
            name: new FullName('User Test'),
            cpf: new Cpf('52998224725'),
            password: new Password('941825'),
            id: 10
        );
        $userRepository->shouldReceive('findByUuid')->with('fake-user-uuid')->andReturn($user);

        $account = new Account(userId: 10, accountNumber: new AccountNumber('987654321-0'), id: 2);
        $accountRepository->shouldReceive('findByUserId')->with(10)->andReturn($account);
        $transactionRepository->shouldReceive('save')->once()->andReturnUsing(function (Transaction $tx) {
            return $tx;
        });

        $useCase = new DepositMoneyUseCase($accountRepository, $eventProducer, $userRepository, $transactionRepository);

        $result = $useCase->execute(null, 25000, 'fake-user-uuid');

        $this->assertEquals('987654321-0', $result['account_number']);
        $this->assertEquals(250.00, $result['amount']);
        $this->assertEquals('pending', $result['status']);
        $this->assertNotEmpty($result['identifier']);
    }

    public function testExecuteThrowsExceptionWhenAccountNotFound(): void
    {
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);
        $eventProducer = Mockery::mock(EventProducerInterface::class);

        $accountRepository->shouldReceive('findByAccountNumber')->with('non-existent')->andReturn(null);

        $useCase = new DepositMoneyUseCase($accountRepository, $eventProducer, $userRepository, $transactionRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Target account not found.');

        $useCase->execute('non-existent', 5000);
    }

    public function testExecuteThrowsExceptionWhenAmountIsFloat(): void
    {
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);
        $eventProducer = Mockery::mock(EventProducerInterface::class);

        $useCase = new DepositMoneyUseCase($accountRepository, $eventProducer, $userRepository, $transactionRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The amount must be an integer in cents.');

        $useCase->execute('123456789-0', 150.50);
    }
}

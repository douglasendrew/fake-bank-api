<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Application\UseCases\Pix;

use App\Application\UseCases\Pix\GetPixTransferStatusUseCase;
use App\Application\UseCases\Pix\SendPixTransferUseCase;
use App\Domain\Account\Entities\Account;
use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Entities\User;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\PixKeyRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\AccountNumber;
use App\Domain\Account\ValueObjects\Cpf;
use App\Domain\Account\ValueObjects\FullName;
use App\Domain\Account\ValueObjects\Money;
use App\Domain\Account\ValueObjects\Password;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

class SendPixTransferUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteSuccessfulPixTransfer(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $pixKeyRepository = Mockery::mock(PixKeyRepositoryInterface::class);
        $driverFactory = Mockery::mock(DriverFactory::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);
        $queueDriver = Mockery::mock(DriverInterface::class);

        $driverFactory->shouldReceive('get')->with('default')->andReturn($queueDriver);
        $queueDriver->shouldReceive('push')->once()->andReturn(true);

        $senderUser = new User(
            name: new FullName('Sender User'),
            cpf: new Cpf('52998224725'),
            password: new Password('941825'),
            id: 1
        );
        $userRepository->shouldReceive('findByUuid')->with('sender-uuid')->andReturn($senderUser);

        $senderAccount = new Account(
            userId: 1,
            accountNumber: new AccountNumber('111111111-1'),
            balance: new Money(500.00),
            id: 1
        );
        $accountRepository->shouldReceive('findByUserId')->with(1)->andReturn($senderAccount);

        $destAccount = new Account(
            userId: 2,
            accountNumber: new AccountNumber('222222222-2'),
            balance: new Money(100.00),
            id: 2
        );
        $accountRepository->shouldReceive('findByAccountNumber')->with('222222222-2')->andReturn($destAccount);

        $transactionRepository->shouldReceive('save')->once()->andReturnUsing(function (Transaction $tx) {
            return $tx;
        });

        $useCase = new SendPixTransferUseCase(
            $userRepository,
            $accountRepository,
            $pixKeyRepository,
            $driverFactory,
            $transactionRepository
        );

        $result = $useCase->execute('sender-uuid', '222222222-2', 'account_number', 5000);

        $this->assertEquals(50.00, $result['amount']);
        $this->assertEquals('pending', $result['status']);
        $this->assertNotEmpty($result['identifier']);
    }

    public function testGetPixTransferStatus(): void
    {
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);

        $tx = new Transaction(
            originAccountId: 1,
            destinationAccountId: 2,
            type: 'pix_transfer',
            amount: new Money(100.00),
            status: 'completed',
            payload: [
                'target' => 'some-key',
                'recipient' => [
                    'name' => 'Dest User',
                    'cpf' => '823.***.***-38',
                    'account_number' => '222222222-2',
                ],
            ]
        );

        $transactionRepository->shouldReceive('findByUuid')->with($tx->getUuid())->andReturn($tx);

        $originAccount = new Account(userId: 1, accountNumber: new AccountNumber('111111111-1'), id: 1);
        $destAccount = new Account(userId: 2, accountNumber: new AccountNumber('222222222-2'), id: 2);
        $accountRepository->shouldReceive('findById')->with(1)->andReturn($originAccount);
        $accountRepository->shouldReceive('findById')->with(2)->andReturn($destAccount);

        $senderUser = new User(
            name: new FullName('Sender Person'),
            cpf: new Cpf('52998224725'),
            password: new Password('941825'),
            id: 1
        );
        $userRepository->shouldReceive('findById')->with(1)->andReturn($senderUser);

        $useCase = new GetPixTransferStatusUseCase($transactionRepository, $accountRepository, $userRepository);
        $result = $useCase->execute($tx->getUuid());

        $this->assertEquals($tx->getUuid(), $result['identifier']);
        $this->assertEquals('completed', $result['status']);
        $this->assertArrayNotHasKey('payload', $result);
        $this->assertEquals('Sender Person', $result['origin']['name']);
        $this->assertEquals('529.***.***-25', $result['origin']['cpf']);
        $this->assertEquals('111111111-1', $result['origin']['account_number']);
        $this->assertEquals('Dest User', $result['recipient']['name']);
        $this->assertEquals('823.***.***-38', $result['recipient']['cpf']);
        $this->assertEquals('222222222-2', $result['recipient']['account_number']);
    }

    public function testSendPixTransferThrowsWhenAmountIsFloat(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $pixKeyRepository = Mockery::mock(PixKeyRepositoryInterface::class);
        $driverFactory = Mockery::mock(DriverFactory::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);

        $useCase = new SendPixTransferUseCase(
            $userRepository,
            $accountRepository,
            $pixKeyRepository,
            $driverFactory,
            $transactionRepository
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The amount must be an integer in cents.');

        $useCase->execute('sender-uuid', '222222222-2', 'account_number', 50.50);
    }
}

<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Application\UseCases\Pix;

use App\Application\UseCases\Pix\ConfirmPixTransferUseCase;
use App\Application\UseCases\Pix\CreatePixTransferUseCase;
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

class CreateAndConfirmPixTransferUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCreatePixTransferReturnsCreatedStatusAndRecipient(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $pixKeyRepository = Mockery::mock(PixKeyRepositoryInterface::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);

        $senderUser = new User(
            name: new FullName('Sender Person'),
            cpf: new Cpf('52998224725'),
            password: new Password('941825'),
            id: 1
        );
        $userRepository->shouldReceive('findByUuid')->with('sender-uuid')->andReturn($senderUser);

        $senderAccount = new Account(
            userId: 1,
            accountNumber: new AccountNumber('111111111-1'),
            balance: new Money(300.00),
            id: 1
        );
        $accountRepository->shouldReceive('findByUserId')->with(1)->andReturn($senderAccount);

        $destUser = new User(
            name: new FullName('Recipient Person'),
            cpf: new Cpf('38927154088'),
            password: new Password('941825'),
            id: 2
        );
        $userRepository->shouldReceive('findById')->with(2)->andReturn($destUser);

        $destAccount = new Account(
            userId: 2,
            accountNumber: new AccountNumber('222222222-2'),
            balance: new Money(50.00),
            id: 2
        );
        $accountRepository->shouldReceive('findByAccountNumber')->with('222222222-2')->andReturn($destAccount);

        $transactionRepository->shouldReceive('save')->once()->andReturnUsing(function (Transaction $tx) {
            return $tx;
        });

        $createUseCase = new CreatePixTransferUseCase(
            $userRepository,
            $accountRepository,
            $pixKeyRepository,
            $transactionRepository
        );

        $result = $createUseCase->execute('sender-uuid', '222222222-2', 'account_number', 7500);

        $this->assertEquals('created', $result['status']);
        $this->assertEquals(75.00, $result['amount']);
        $this->assertNotEmpty($result['identifier']);
        $this->assertEquals('Recipient Person', $result['recipient']['name']);
        $this->assertEquals('389.***.***-88', $result['recipient']['cpf']);
        $this->assertEquals('222222222-2', $result['recipient']['account_number']);
    }

    public function testConfirmPixTransferTransitionsToProcessingAndEnqueuesJob(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);
        $driverFactory = Mockery::mock(DriverFactory::class);
        $queueDriver = Mockery::mock(DriverInterface::class);

        $driverFactory->shouldReceive('get')->with('default')->andReturn($queueDriver);
        $queueDriver->shouldReceive('push')->once()->andReturn(true);

        $senderUser = new User(
            name: new FullName('Sender Person'),
            cpf: new Cpf('52998224725'),
            password: new Password('941825'),
            id: 1
        );
        $userRepository->shouldReceive('findByUuid')->with('sender-uuid')->andReturn($senderUser);

        $senderAccount = new Account(
            userId: 1,
            accountNumber: new AccountNumber('111111111-1'),
            balance: new Money(300.00),
            id: 1
        );
        $accountRepository->shouldReceive('findByUserId')->with(1)->andReturn($senderAccount);

        $transaction = new Transaction(
            originAccountId: 1,
            destinationAccountId: 2,
            type: 'pix_transfer',
            amount: new Money(75.00),
            status: 'created',
            payload: [
                'transfer_type' => 'account_number',
                'target_key_or_account' => '222222222-2',
                'recipient' => [
                    'name' => 'Recipient Person',
                    'cpf' => '389.***.***-88',
                    'account_number' => '222222222-2',
                ],
            ]
        );
        $transactionRepository->shouldReceive('findByUuid')->with($transaction->getUuid())->andReturn($transaction);
        $transactionRepository->shouldReceive('save')->once()->andReturnUsing(function (Transaction $tx) {
            return $tx;
        });

        $confirmUseCase = new ConfirmPixTransferUseCase(
            $userRepository,
            $accountRepository,
            $transactionRepository,
            $driverFactory
        );

        $result = $confirmUseCase->execute('sender-uuid', $transaction->getUuid());

        $this->assertEquals('processing', $result['status']);
        $this->assertEquals(75.00, $result['amount']);
        $this->assertEquals($transaction->getUuid(), $result['identifier']);
        $this->assertEquals('Recipient Person', $result['recipient']['name']);
    }

    public function testConfirmPixTransferThrowsWhenAlreadyConfirmed(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);
        $driverFactory = Mockery::mock(DriverFactory::class);

        $senderUser = new User(
            name: new FullName('Sender Person'),
            cpf: new Cpf('52998224725'),
            password: new Password('941825'),
            id: 1
        );
        $userRepository->shouldReceive('findByUuid')->with('sender-uuid')->andReturn($senderUser);

        $senderAccount = new Account(
            userId: 1,
            accountNumber: new AccountNumber('111111111-1'),
            balance: new Money(300.00),
            id: 1
        );
        $accountRepository->shouldReceive('findByUserId')->with(1)->andReturn($senderAccount);

        $transaction = new Transaction(
            originAccountId: 1,
            destinationAccountId: 2,
            type: 'pix_transfer',
            amount: new Money(75.00),
            status: 'processing'
        );
        $transactionRepository->shouldReceive('findByUuid')->with($transaction->getUuid())->andReturn($transaction);

        $confirmUseCase = new ConfirmPixTransferUseCase(
            $userRepository,
            $accountRepository,
            $transactionRepository,
            $driverFactory
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction cannot be confirmed. Current status: processing.');

        $confirmUseCase->execute('sender-uuid', $transaction->getUuid());
    }

    public function testCreatePixTransferThrowsWhenAmountIsFloat(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $pixKeyRepository = Mockery::mock(PixKeyRepositoryInterface::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);

        $createUseCase = new CreatePixTransferUseCase(
            $userRepository,
            $accountRepository,
            $pixKeyRepository,
            $transactionRepository
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The amount must be an integer in cents.');

        $createUseCase->execute('sender-uuid', '222222222-2', 'account_number', 75.50);
    }
}

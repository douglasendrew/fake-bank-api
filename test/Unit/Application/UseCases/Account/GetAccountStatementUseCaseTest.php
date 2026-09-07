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

use App\Application\UseCases\Account\GetAccountStatementUseCase;
use App\Domain\Account\Entities\Account;
use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Entities\User;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\AccountNumber;
use App\Domain\Account\ValueObjects\Cpf;
use App\Domain\Account\ValueObjects\FullName;
use App\Domain\Account\ValueObjects\Money;
use App\Domain\Account\ValueObjects\Password;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class GetAccountStatementUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetAccountStatementWithPixOutPixInAndDeposit(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);

        $currentUser = new User(
            name: new FullName('Current User'),
            cpf: new Cpf('52998224725'),
            password: new Password('941825'),
            id: 1
        );
        $userRepository->shouldReceive('findByUuid')->with('user-uuid-1')->andReturn($currentUser);

        $currentAccount = new Account(
            userId: 1,
            accountNumber: new AccountNumber('111111111-1'),
            balance: new Money(1000.00),
            id: 10
        );
        $accountRepository->shouldReceive('findByUserId')->with(1)->andReturn($currentAccount);

        // 1. Pix Out (current account 10 sent to 20)
        $txPixOut = new Transaction(
            originAccountId: 10,
            destinationAccountId: 20,
            type: 'pix_transfer',
            amount: new Money(75.00),
            status: 'completed',
            payload: [
                'recipient' => [
                    'name' => 'Recipient User',
                    'cpf' => '389.***.***-88',
                    'account_number' => '222222222-2',
                ],
            ]
        );

        // 2. Pix In (account 30 sent to current account 10)
        $txPixIn = new Transaction(
            originAccountId: 30,
            destinationAccountId: 10,
            type: 'pix_transfer',
            amount: new Money(150.00),
            status: 'completed'
        );

        $senderAccount = new Account(userId: 3, accountNumber: new AccountNumber('333333333-3'), id: 30);
        $senderUser = new User(
            name: new FullName('Origin User'),
            cpf: new Cpf('33079126823'),
            password: new Password('941825'),
            id: 3
        );
        $accountRepository->shouldReceive('findById')->with(30)->andReturn($senderAccount);
        $userRepository->shouldReceive('findById')->with(3)->andReturn($senderUser);

        // 3. Deposit
        $txDeposit = new Transaction(
            originAccountId: null,
            destinationAccountId: 10,
            type: 'deposit',
            amount: new Money(500.00),
            status: 'completed'
        );

        $transactionRepository->shouldReceive('findAllByAccountId')
            ->with(10)
            ->andReturn([$txPixOut, $txPixIn, $txDeposit]);

        $useCase = new GetAccountStatementUseCase($userRepository, $accountRepository, $transactionRepository);
        $statement = $useCase->execute('user-uuid-1');

        $this->assertCount(3, $statement);

        // Item 1: pix_out
        $this->assertEquals($txPixOut->getUuid(), $statement[0]['identifier']);
        $this->assertEquals('pix_out', $statement[0]['type']);
        $this->assertEquals(75.00, $statement[0]['amount']);
        $this->assertEquals('Recipient User', $statement[0]['data']['recipient']['name']);
        $this->assertEquals('Recipient User', $statement[0]['data']['receipt']['name']);
        $this->assertNotEmpty($statement[0]['date']);

        // Item 2: pix_in
        $this->assertEquals($txPixIn->getUuid(), $statement[1]['identifier']);
        $this->assertEquals('pix_in', $statement[1]['type']);
        $this->assertEquals(150.00, $statement[1]['amount']);
        $this->assertEquals('Origin User', $statement[1]['data']['origin']['name']);
        $this->assertEquals('330.***.***-23', $statement[1]['data']['origin']['cpf']);
        $this->assertEquals('333333333-3', $statement[1]['data']['origin']['account_number']);
        $this->assertNotEmpty($statement[1]['date']);

        // Item 3: deposit
        $this->assertEquals($txDeposit->getUuid(), $statement[2]['identifier']);
        $this->assertEquals('deposit', $statement[2]['type']);
        $this->assertEquals(500.00, $statement[2]['amount']);
        $this->assertEquals('111111111-1', $statement[2]['data']['account_number']);
        $this->assertNotEmpty($statement[2]['date']);
    }

    public function testGetAccountStatementThrowsWhenUserNotFound(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);

        $userRepository->shouldReceive('findByUuid')->with('invalid-uuid')->andReturn(null);

        $useCase = new GetAccountStatementUseCase($userRepository, $accountRepository, $transactionRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User not found.');

        $useCase->execute('invalid-uuid');
    }
}

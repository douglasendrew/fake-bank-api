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

use App\Application\Handlers\ProcessDepositHandler;
use App\Domain\Account\Entities\Account;
use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\ValueObjects\AccountNumber;
use App\Domain\Account\ValueObjects\Money;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ProcessDepositHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testHandleDepositsToAccountSuccessfullyWithTransactionUuid(): void
    {
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);

        $account = new Account(
            userId: 1,
            accountNumber: new AccountNumber('123456789-0'),
            balance: new Money(100.00),
            id: 1
        );

        $tx = new Transaction(
            originAccountId: null,
            destinationAccountId: 1,
            type: 'deposit',
            amount: new Money(50.00),
            status: 'pending'
        );

        $accountRepository->shouldReceive('findByAccountNumber')->with('123456789-0')->andReturn($account);
        $accountRepository->shouldReceive('save')->once()->with($account);
        $transactionRepository->shouldReceive('findByUuid')->with($tx->getUuid())->andReturn($tx);
        $transactionRepository->shouldReceive('save')->once()->with(Mockery::on(function (Transaction $savedTx) {
            return $savedTx->getStatus() === 'completed';
        }));

        $handler = new ProcessDepositHandler($accountRepository, $transactionRepository);
        $handler->handle('123456789-0', 50.00, $tx->getUuid());

        $this->assertEquals(150.00, $account->getBalance()->getAmount());
        $this->assertEquals('completed', $tx->getStatus());
    }

    public function testHandleFailsTransactionWhenAccountNotFound(): void
    {
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);

        $tx = new Transaction(
            originAccountId: null,
            destinationAccountId: 1,
            type: 'deposit',
            amount: new Money(50.00),
            status: 'pending'
        );

        $accountRepository->shouldReceive('findByAccountNumber')->with('non-existent')->andReturn(null);
        $transactionRepository->shouldReceive('findByUuid')->with($tx->getUuid())->andReturn($tx);
        $transactionRepository->shouldReceive('save')->once()->with(Mockery::on(function (Transaction $savedTx) {
            return $savedTx->getStatus() === 'failed';
        }));

        $handler = new ProcessDepositHandler($accountRepository, $transactionRepository);
        $handler->handle('non-existent', 50.00, $tx->getUuid());

        $this->assertEquals('failed', $tx->getStatus());
    }
}

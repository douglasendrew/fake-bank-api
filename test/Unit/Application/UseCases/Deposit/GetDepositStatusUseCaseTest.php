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

use App\Application\UseCases\Deposit\GetDepositStatusUseCase;
use App\Domain\Account\Entities\Account;
use App\Domain\Account\Entities\Transaction;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\ValueObjects\AccountNumber;
use App\Domain\Account\ValueObjects\Money;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class GetDepositStatusUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetDepositStatusSuccess(): void
    {
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);

        $tx = new Transaction(
            originAccountId: null,
            destinationAccountId: 5,
            type: 'deposit',
            amount: new Money(200.00),
            status: 'completed'
        );

        $transactionRepository->shouldReceive('findByUuid')->with($tx->getUuid())->andReturn($tx);

        $account = new Account(userId: 1, accountNumber: new AccountNumber('123456789-0'), id: 5);
        $accountRepository->shouldReceive('findById')->with(5)->andReturn($account);

        $useCase = new GetDepositStatusUseCase($transactionRepository, $accountRepository);
        $result = $useCase->execute($tx->getUuid());

        $this->assertEquals($tx->getUuid(), $result['identifier']);
        $this->assertEquals('deposit', $result['type']);
        $this->assertEquals('123456789-0', $result['account_number']);
        $this->assertEquals(200.00, $result['amount']);
        $this->assertEquals('completed', $result['status']);
    }

    public function testGetDepositStatusThrowsWhenNotFound(): void
    {
        $transactionRepository = Mockery::mock(TransactionRepositoryInterface::class);
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);

        $transactionRepository->shouldReceive('findByUuid')->with('invalid-uuid')->andReturn(null);

        $useCase = new GetDepositStatusUseCase($transactionRepository, $accountRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Deposit transaction not found.');

        $useCase->execute('invalid-uuid');
    }
}

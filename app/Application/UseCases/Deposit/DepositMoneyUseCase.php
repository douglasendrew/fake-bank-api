<?php

declare(strict_types=1);

namespace App\Application\UseCases\Deposit;

use App\Application\Jobs\ProcessDepositJob;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Money;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use InvalidArgumentException;

class DepositMoneyUseCase
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private DriverFactory $driverFactory,
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(?string $accountNumber, float $amount, ?string $userUuid = null): array
    {
        $money = new Money($amount);

        $account = null;
        if (! empty($accountNumber)) {
            $account = $this->accountRepository->findByAccountNumber($accountNumber);
        } elseif (! empty($userUuid)) {
            $user = $this->userRepository->findByUuid($userUuid);
            if ($user) {
                $account = $this->accountRepository->findByUserId($user->getId());
            }
        }

        if (! $account) {
            throw new InvalidArgumentException('Target account not found.');
        }

        // Push to async processing queue
        $driver = $this->driverFactory->get('default');
        $driver->push(new ProcessDepositJob($account->getAccountNumber()->getValue(), $money->getAmount()));

        return [
            'message' => 'Deposit requested and queued for processing successfully.',
            'account_number' => $account->getAccountNumber()->getValue(),
            'amount' => $money->getAmount(),
            'status' => 'pending',
        ];
    }
}

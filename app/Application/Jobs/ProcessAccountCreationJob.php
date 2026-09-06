<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Domain\Account\Entities\Account;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\AccountNumber;
use AllowDynamicProperties;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;

#[AllowDynamicProperties]
class ProcessAccountCreationJob extends Job
{
    public string $userUuid;

    public function __construct(string $userUuid)
    {
        $this->userUuid = $userUuid;
    }

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $container->get(UserRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = $container->get(AccountRepositoryInterface::class);

        $user = $userRepository->findByUuid($this->userUuid);
        if (! $user) {
            return;
        }

        // Generate unique account number
        do {
            $accountNumber = AccountNumber::generate();
            $existingAccount = $accountRepository->findByAccountNumber($accountNumber->getValue());
        } while ($existingAccount !== null);

        // Create Account entity
        $account = new Account(
            userId: $user->getId(),
            accountNumber: $accountNumber
        );

        $accountRepository->save($account);

        // Mark user as approved
        $user->setStatus('approved');
        $userRepository->save($user);
    }
}

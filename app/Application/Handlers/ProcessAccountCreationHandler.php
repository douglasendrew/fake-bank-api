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

namespace App\Application\Handlers;

use App\Domain\Account\Entities\Account;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\AccountNumber;

class ProcessAccountCreationHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AccountRepositoryInterface $accountRepository
    ) {
    }

    public function handle(string $userUuid): void
    {
        $user = $this->userRepository->findByUuid($userUuid);
        if (! $user) {
            return;
        }

        // Generate unique account number
        do {
            $accountNumber = AccountNumber::generate();
            $existingAccount = $this->accountRepository->findByAccountNumber($accountNumber->getValue());
        } while ($existingAccount !== null);

        // Create Account entity
        $account = new Account(
            userId: $user->getId(),
            accountNumber: $accountNumber
        );

        $this->accountRepository->save($account);

        // Mark user as approved
        $user->setStatus('approved');
        $this->userRepository->save($user);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\UseCases\Account;

use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use InvalidArgumentException;

class GetAccountInfoUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AccountRepositoryInterface $accountRepository
    ) {}

    public function execute(string $userUuid): array
    {
        $user = $this->userRepository->findByUuid($userUuid);
        if (! $user) {
            throw new InvalidArgumentException('User not found.');
        }

        $account = $this->accountRepository->findByUserId($user->getId());
        if (! $account) {
            throw new InvalidArgumentException('Account not yet activated or found.');
        }

        return [
            'name' => $user->getName()->getValue(),
            'cpf' => $user->getCpf()->getMasked(),
            'account_number' => $account->getAccountNumber()->getValue(),
            'balance' => $account->getBalance()->getAmount(),
        ];
    }
}

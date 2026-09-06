<?php

declare(strict_types=1);

namespace App\Application\UseCases\Pix;

use App\Domain\Account\Entities\PixKey;
use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\PixKeyRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Cpf;
use InvalidArgumentException;

class RegisterPixKeyUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AccountRepositoryInterface $accountRepository,
        private PixKeyRepositoryInterface $pixKeyRepository
    ) {}

    public function execute(string $userUuid, string $type, string $keyInput): array
    {
        $user = $this->userRepository->findByUuid($userUuid);
        if (! $user) {
            throw new InvalidArgumentException('User not found.');
        }

        $account = $this->accountRepository->findByUserId($user->getId());
        if (! $account) {
            throw new InvalidArgumentException('Account not found.');
        }

        // Limit: Max 1 PIX key per user
        $existingUserKey = $this->pixKeyRepository->findByUserId($user->getId());
        if ($existingUserKey !== null) {
            throw new InvalidArgumentException('User already has a registered PIX key. Only one PIX key is allowed per user.');
        }

        $cleanKey = trim($keyInput);
        if (strtolower($type) === 'cpf') {
            $cpf = new Cpf($cleanKey);
            $cleanKey = $cpf->getUnformatted();
        }

        // Validate key uniqueness across all users
        $duplicateKey = $this->pixKeyRepository->findByKey($cleanKey);
        if ($duplicateKey !== null) {
            throw new InvalidArgumentException('This PIX key is already in use by another user.');
        }

        $pixKey = new PixKey(
            userId: $user->getId(),
            accountId: $account->getId(),
            type: $type,
            key: $cleanKey
        );

        $savedPixKey = $this->pixKeyRepository->save($pixKey);

        return [
            'identifier' => $savedPixKey->getUuid(),
            'type' => $savedPixKey->getType(),
            'key' => $savedPixKey->getKey(),
            'created_at' => $savedPixKey->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}

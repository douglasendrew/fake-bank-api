<?php

declare(strict_types=1);

namespace App\Application\UseCases\Pix;

use App\Domain\Account\Repositories\PixKeyRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use InvalidArgumentException;

class ListPixKeysUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PixKeyRepositoryInterface $pixKeyRepository
    ) {}

    public function execute(string $userUuid): array
    {
        $user = $this->userRepository->findByUuid($userUuid);
        if (! $user) {
            throw new InvalidArgumentException('User not found.');
        }

        $pixKeys = $this->pixKeyRepository->findAllByUserId($user->getId());

        $result = [];
        foreach ($pixKeys as $pixKey) {
            $result[] = [
                'identifier' => $pixKey->getUuid(),
                'type' => $pixKey->getType(),
                'key' => $pixKey->getKey(),
                'created_at' => $pixKey->getCreatedAt()->format(DATE_ATOM),
            ];
        }

        return $result;
    }
}

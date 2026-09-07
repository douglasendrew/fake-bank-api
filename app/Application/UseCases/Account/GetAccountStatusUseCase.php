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

namespace App\Application\UseCases\Account;

use App\Domain\Account\Repositories\UserRepositoryInterface;
use InvalidArgumentException;

class GetAccountStatusUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function execute(string $userUuid): array
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (! $user) {
            throw new InvalidArgumentException('Account not found.');
        }

        return [
            'identifier' => $user->getUuid(),
            'status' => $user->getStatus(),
            'created_at' => $user->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $user->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}

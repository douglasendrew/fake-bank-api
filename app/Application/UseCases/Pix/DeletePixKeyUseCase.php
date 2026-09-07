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

namespace App\Application\UseCases\Pix;

use App\Domain\Account\Repositories\PixKeyRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Cpf;
use InvalidArgumentException;

class DeletePixKeyUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PixKeyRepositoryInterface $pixKeyRepository
    ) {
    }

    public function execute(string $userUuid, string $keyInput): array
    {
        $user = $this->userRepository->findByUuid($userUuid);
        if (! $user) {
            throw new InvalidArgumentException('User not found.');
        }

        $cleanKey = trim($keyInput);
        if (preg_match('/^\d{11}$|^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $cleanKey)) {
            try {
                $cpf = new Cpf($cleanKey);
                $cleanKey = $cpf->getUnformatted();
            } catch (InvalidArgumentException $e) {
                // Not a valid CPF, keep as is for email search
            }
        }

        $pixKey = $this->pixKeyRepository->findByKey($cleanKey);
        if (! $pixKey || $pixKey->getUserId() !== $user->getId()) {
            throw new InvalidArgumentException('PIX key not found for this user.');
        }

        $this->pixKeyRepository->delete($pixKey);

        return [
            'message' => 'PIX key deleted successfully.',
            'key' => $keyInput,
        ];
    }
}

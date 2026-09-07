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

use App\Application\Common\Contracts\EventProducerInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Cpf;
use App\Domain\Account\ValueObjects\FullName;
use App\Domain\Account\ValueObjects\Password;
use InvalidArgumentException;

class UpdateAccountUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventProducerInterface $eventProducer
    ) {
    }

    public function execute(string $userUuid, string $name, string $cpfInput, string $passwordInput): array
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (! $user) {
            throw new InvalidArgumentException('Account not found.');
        }

        $fullName = new FullName($name);
        $cpf = new Cpf($cpfInput);
        $password = new Password($passwordInput);

        // Check if CPF belongs to another user
        $existingCpfUser = $this->userRepository->findByCpf($cpf->getUnformatted());
        if ($existingCpfUser !== null && $existingCpfUser->getUuid() !== $user->getUuid()) {
            throw new InvalidArgumentException('This CPF is already in use by another user.');
        }

        $user->update($fullName, $cpf, $password);
        $updatedUser = $this->userRepository->save($user);

        // Re-publish account creation/activation event to Kafka
        $this->eventProducer->publish('bank.account.creation', [
            'user_uuid' => $updatedUser->getUuid(),
            'timestamp' => time(),
        ], $updatedUser->getUuid());

        return [
            'identifier' => $updatedUser->getUuid(),
            'name' => $updatedUser->getName()->getValue(),
            'cpf' => $updatedUser->getCpf()->getMasked(),
            'status' => $updatedUser->getStatus(),
            'updated_at' => $updatedUser->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}

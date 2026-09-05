<?php

declare(strict_types=1);

namespace App\Application\UseCases\Account;

use App\Application\Jobs\ProcessAccountCreationJob;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Cpf;
use App\Domain\Account\ValueObjects\FullName;
use App\Domain\Account\ValueObjects\Password;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use InvalidArgumentException;

class UpdateAccountUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private DriverFactory $driverFactory
    ) {}

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

        // Re-enqueue account processing
        $driver = $this->driverFactory->get('default');
        $driver->push(new ProcessAccountCreationJob($updatedUser->getUuid()));

        return [
            'uuid' => $updatedUser->getUuid(),
            'name' => $updatedUser->getName()->getValue(),
            'cpf' => $updatedUser->getCpf()->getMasked(),
            'status' => $updatedUser->getStatus(),
            'updated_at' => $updatedUser->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Application\UseCases\Account;

use App\Application\Jobs\ProcessAccountCreationJob;
use App\Domain\Account\Entities\User;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Account\ValueObjects\Cpf;
use App\Domain\Account\ValueObjects\FullName;
use App\Domain\Account\ValueObjects\Password;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use InvalidArgumentException;

class CreateAccountUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private DriverFactory $driverFactory
    ) {}

    public function execute(string $name, string $cpfInput, string $passwordInput): array
    {
        $fullName = new FullName($name);
        $cpf = new Cpf($cpfInput);
        $password = new Password($passwordInput);

        if ($this->userRepository->findByCpf($cpf->getUnformatted()) !== null) {
            throw new InvalidArgumentException('A user with this CPF already exists.');
        }

        $user = new User(
            name: $fullName,
            cpf: $cpf,
            password: $password,
            status: 'pending_creation'
        );

        $savedUser = $this->userRepository->save($user);

        // Enqueue account creation processing job
        $driver = $this->driverFactory->get('default');
        $driver->push(new ProcessAccountCreationJob($savedUser->getUuid()));

        return [
            'identifier' => $savedUser->getUuid(),
            'name' => $savedUser->getName()->getValue(),
            'cpf' => $savedUser->getCpf()->getMasked(),
            'status' => $savedUser->getStatus(),
            'created_at' => $savedUser->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}

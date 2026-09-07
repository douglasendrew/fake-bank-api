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

namespace App\Application\UseCases\Auth;

use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Logging\Entities\LogLogin;
use App\Domain\Logging\Repositories\LogLoginRepositoryInterface;
use App\Infrastructure\Security\JwtService;
use InvalidArgumentException;

class AuthenticateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private LogLoginRepositoryInterface $logLoginRepository,
        private JwtService $jwtService
    ) {
    }

    public function execute(string $cpfInput, string $rawPassword): array
    {
        $cleanCpf = preg_replace('/\D/', '', $cpfInput);

        $user = $this->userRepository->findByCpf($cleanCpf);
        $success = false;
        $userId = null;

        if ($user !== null) {
            $userId = $user->getId();
            if ($user->getPassword()->verify($rawPassword)) {
                $success = true;
            }
        }

        // Log login attempt in fb_logs_login
        $logLogin = new LogLogin(
            credential: $cpfInput,
            loginSuccessfully: $success,
            userId: $userId
        );
        $this->logLoginRepository->save($logLogin);

        if (! $success || $user === null) {
            throw new InvalidArgumentException('Invalid CPF or password.');
        }

        // Generate 15-minute JWT using user UUID
        $token = $this->jwtService->generateToken($user->getUuid());

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 900, // 15 minutes
            'user' => [
                'identifier' => $user->getUuid(),
                'name' => $user->getName()->getValue(),
                'cpf' => $user->getCpf()->getMasked(),
                'status' => $user->getStatus(),
            ],
        ];
    }
}

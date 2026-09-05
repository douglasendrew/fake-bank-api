<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;

class JwtService
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = getenv('JWT_SECRET') ?: 'FakeBankSuperSecretJwtKey2026SecretKey32Bytes!';
    }

    public function generateToken(string $userUuid): string
    {
        $issuedAt = time();
        $expire = $issuedAt + (15 * 60); // 15 minutes validity

        $payload = [
            'iss' => 'FakeBankAPI',
            'iat' => $issuedAt,
            'exp' => $expire,
            'user_uuid' => $userUuid,
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function decodeToken(string $token): object
    {
        try {
            return JWT::decode($token, new Key($this->secretKey, 'HS256'));
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('Invalid or expired authentication token.');
        }
    }
}

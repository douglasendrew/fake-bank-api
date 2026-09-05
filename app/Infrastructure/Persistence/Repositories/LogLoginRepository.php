<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Logging\Entities\LogLogin;
use App\Domain\Logging\Repositories\LogLoginRepositoryInterface;
use DateTimeImmutable;
use Hyperf\DbConnection\Db;

class LogLoginRepository implements LogLoginRepositoryInterface
{
    public function save(LogLogin $logLogin): LogLogin
    {
        $id = Db::table('fb_logs_login')->insertGetId([
            'll_uuid' => $logLogin->getUuid(),
            'us_id' => $logLogin->getUserId(),
            'll_credential' => $logLogin->getCredential(),
            'll_login_successfully' => $logLogin->isLoginSuccessfully(),
            'll_created_at' => $logLogin->getCreatedAt()->format('Y-m-d H:i:s'),
        ], 'll_id');

        return new LogLogin(
            credential: $logLogin->getCredential(),
            loginSuccessfully: $logLogin->isLoginSuccessfully(),
            userId: $logLogin->getUserId(),
            id: (int) $id,
            uuid: $logLogin->getUuid(),
            createdAt: $logLogin->getCreatedAt()
        );
    }
}

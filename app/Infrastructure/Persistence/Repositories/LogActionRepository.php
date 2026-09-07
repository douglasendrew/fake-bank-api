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

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Logging\Entities\LogAction;
use App\Domain\Logging\Repositories\LogActionRepositoryInterface;
use Hyperf\DbConnection\Db;

class LogActionRepository implements LogActionRepositoryInterface
{
    public function save(LogAction $logAction): LogAction
    {
        $id = Db::table('fb_logs_action')->insertGetId([
            'la_uuid' => $logAction->getUuid(),
            'us_id' => $logAction->getUserId(),
            'er_id' => $logAction->getErrorId(),
            'la_action' => $logAction->getAction(),
            'la_action_metadata' => json_encode($logAction->getActionMetadata()),
            'la_error' => $logAction->isError(),
            'la_created_at' => $logAction->getCreatedAt()->format('Y-m-d H:i:s'),
        ], 'la_id');

        return new LogAction(
            action: $logAction->getAction(),
            actionMetadata: $logAction->getActionMetadata(),
            error: $logAction->isError(),
            userId: $logAction->getUserId(),
            errorId: $logAction->getErrorId(),
            id: (int) $id,
            uuid: $logAction->getUuid(),
            createdAt: $logAction->getCreatedAt()
        );
    }
}

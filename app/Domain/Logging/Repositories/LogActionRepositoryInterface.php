<?php

declare(strict_types=1);

namespace App\Domain\Logging\Repositories;

use App\Domain\Logging\Entities\LogAction;

interface LogActionRepositoryInterface
{
    public function save(LogAction $logAction): LogAction;
}

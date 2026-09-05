<?php

declare(strict_types=1);

namespace App\Domain\Logging\Repositories;

use App\Domain\Logging\Entities\LogError;

interface LogErrorRepositoryInterface
{
    public function save(LogError $logError): LogError;
}

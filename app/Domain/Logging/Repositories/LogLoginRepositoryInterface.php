<?php

declare(strict_types=1);

namespace App\Domain\Logging\Repositories;

use App\Domain\Logging\Entities\LogLogin;

interface LogLoginRepositoryInterface
{
    public function save(LogLogin $logLogin): LogLogin;
}

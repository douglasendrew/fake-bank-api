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

namespace App\Domain\Logging\Repositories;

use App\Domain\Logging\Entities\LogError;

interface LogErrorRepositoryInterface
{
    public function save(LogError $logError): LogError;
}

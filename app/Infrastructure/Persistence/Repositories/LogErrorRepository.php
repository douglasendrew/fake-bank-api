<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Logging\Entities\LogError;
use App\Domain\Logging\Repositories\LogErrorRepositoryInterface;
use Hyperf\DbConnection\Db;

class LogErrorRepository implements LogErrorRepositoryInterface
{
    public function save(LogError $logError): LogError
    {
        $id = Db::table('fb_log_error')->insertGetId([
            'er_uuid' => $logError->getUuid(),
            'er_page' => $logError->getPage(),
            'er_file' => $logError->getFile(),
            'er_method' => $logError->getMethod(),
            'er_line' => $logError->getLine(),
            'er_message' => $logError->getMessage(),
            'er_data' => json_encode($logError->getData()),
            'er_data_sanitized' => $logError->getDataSanitized() ? json_encode($logError->getDataSanitized()) : null,
            'er_date_error' => $logError->getDateError()->format('Y-m-d H:i:s'),
        ], 'er_id');

        return new LogError(
            page: $logError->getPage(),
            file: $logError->getFile(),
            method: $logError->getMethod(),
            line: $logError->getLine(),
            message: $logError->getMessage(),
            data: $logError->getData(),
            dataSanitized: $logError->getDataSanitized(),
            id: (int) $id,
            uuid: $logError->getUuid(),
            dateError: $logError->getDateError()
        );
    }
}

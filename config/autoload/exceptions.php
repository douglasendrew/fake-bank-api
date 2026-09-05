<?php

declare(strict_types=1);

use App\Interfaces\Exception\Handlers\GlobalExceptionHandler;

return [
    'handler' => [
        'http' => [
            GlobalExceptionHandler::class,
        ],
    ],
];

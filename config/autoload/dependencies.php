<?php

declare(strict_types=1);

use App\Domain\Account\Repositories\AccountRepositoryInterface;
use App\Domain\Account\Repositories\PixKeyRepositoryInterface;
use App\Domain\Account\Repositories\TransactionRepositoryInterface;
use App\Domain\Account\Repositories\UserRepositoryInterface;
use App\Domain\Logging\Repositories\LogActionRepositoryInterface;
use App\Domain\Logging\Repositories\LogErrorRepositoryInterface;
use App\Domain\Logging\Repositories\LogLoginRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\AccountRepository;
use App\Infrastructure\Persistence\Repositories\LogActionRepository;
use App\Infrastructure\Persistence\Repositories\LogErrorRepository;
use App\Infrastructure\Persistence\Repositories\LogLoginRepository;
use App\Infrastructure\Persistence\Repositories\PixKeyRepository;
use App\Infrastructure\Persistence\Repositories\TransactionRepository;
use App\Infrastructure\Persistence\Repositories\UserRepository;

return [
    UserRepositoryInterface::class => UserRepository::class,
    AccountRepositoryInterface::class => AccountRepository::class,
    PixKeyRepositoryInterface::class => PixKeyRepository::class,
    TransactionRepositoryInterface::class => TransactionRepository::class,
    LogLoginRepositoryInterface::class => LogLoginRepository::class,
    LogActionRepositoryInterface::class => LogActionRepository::class,
    LogErrorRepositoryInterface::class => LogErrorRepository::class,
];

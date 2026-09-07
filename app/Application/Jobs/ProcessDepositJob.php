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

namespace App\Application\Jobs;

use AllowDynamicProperties;
use App\Application\Handlers\ProcessDepositHandler;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;

#[AllowDynamicProperties]
class ProcessDepositJob extends Job
{
    public string $accountNumber;

    public float $amount;

    public ?string $transactionUuid = null;

    public function __construct(
        string $accountNumber,
        float $amount,
        ?string $transactionUuid = null
    ) {
        $this->accountNumber = $accountNumber;
        $this->amount = $amount;
        $this->transactionUuid = $transactionUuid;
    }

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var ProcessDepositHandler $handler */
        $handler = $container->get(ProcessDepositHandler::class);
        $handler->handle($this->accountNumber, $this->amount, $this->transactionUuid);
    }
}

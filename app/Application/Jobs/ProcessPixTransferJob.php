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
use App\Application\Handlers\ProcessPixTransferHandler;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;

#[AllowDynamicProperties]
class ProcessPixTransferJob extends Job
{
    public int $senderAccountId;

    public int $destinationAccountId;

    public float $amount;

    public string $type;

    public string $targetKeyOrAccount;

    public ?string $transactionUuid = null;

    public function __construct(
        int $senderAccountId,
        int $destinationAccountId,
        float $amount,
        string $type,
        string $targetKeyOrAccount,
        ?string $transactionUuid = null
    ) {
        $this->senderAccountId = $senderAccountId;
        $this->destinationAccountId = $destinationAccountId;
        $this->amount = $amount;
        $this->type = $type;
        $this->targetKeyOrAccount = $targetKeyOrAccount;
        $this->transactionUuid = $transactionUuid;
    }

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var ProcessPixTransferHandler $handler */
        $handler = $container->get(ProcessPixTransferHandler::class);
        $handler->handle(
            $this->senderAccountId,
            $this->destinationAccountId,
            $this->amount,
            $this->type,
            $this->targetKeyOrAccount,
            $this->transactionUuid
        );
    }
}

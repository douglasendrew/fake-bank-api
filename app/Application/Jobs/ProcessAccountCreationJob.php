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
use App\Application\Handlers\ProcessAccountCreationHandler;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;

#[AllowDynamicProperties]
class ProcessAccountCreationJob extends Job
{
    public string $userUuid;

    public function __construct(string $userUuid)
    {
        $this->userUuid = $userUuid;
    }

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var ProcessAccountCreationHandler $handler */
        $handler = $container->get(ProcessAccountCreationHandler::class);
        $handler->handle($this->userUuid);
    }
}

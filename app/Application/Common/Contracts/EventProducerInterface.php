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

namespace App\Application\Common\Contracts;

interface EventProducerInterface
{
    /**
     * Publish an event payload to a topic.
     *
     * @param string $topic Topic name
     * @param array<string, mixed> $payload Event data payload
     * @param null|string $key Optional partition key
     */
    public function publish(string $topic, array $payload, ?string $key = null): void;
}

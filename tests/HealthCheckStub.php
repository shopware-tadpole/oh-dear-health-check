<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Tests;

use Tadpole\OhDear\Check\HealthCheck;
use Tadpole\OhDear\Enum\HealthStatus;
use Tadpole\OhDear\Struct\HealthCheckStruct;

final class HealthCheckStub implements HealthCheck
{
    public int $calls = 0;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        private readonly string $name,
        private readonly HealthStatus $status,
        private readonly string $message = '',
        private readonly string $summary = '',
        private readonly array $meta = [],
    ) {
    }

    public function getHealthCheckStruct(): HealthCheckStruct
    {
        ++$this->calls;

        $struct = new HealthCheckStruct();
        $struct->setName($this->name);
        $struct->setLabel($this->name . ' label');
        $struct->setStatus($this->status);
        $struct->setNotificationMessage($this->message);
        $struct->setShortSummary($this->summary);
        $struct->setMeta($this->meta);

        return $struct;
    }
}

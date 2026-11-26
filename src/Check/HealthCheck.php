<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Check;

use Tadpole\OhDear\Struct\HealthCheckStruct;

interface HealthCheck
{
    public function getHealthCheckStruct(): HealthCheckStruct;
}

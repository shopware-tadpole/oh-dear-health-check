<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Enum;

enum HealthStatus: string
{
    /**
     * ok: the check ran ok
     */
    case OK = 'ok';
    /**
     * warning: the check is closed to failing
     */
    case WARNING = 'warning';
    /**
     * failed: the check did fail
     */
    case FAILED = 'failed';
    /**
     * crashed: something went wrong running the check itself
     */
    case CRASHED = 'crashed';
    /**
     * skipped: the check wasn't performed at all
     */
    case SKIPPED = 'skipped';
}

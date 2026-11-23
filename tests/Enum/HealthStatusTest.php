<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tadpole\OhDear\Enum\HealthStatus;

class HealthStatusTest extends TestCase
{
    public function testValuesMatchOhDearApplicationHealthStatuses(): void
    {
        self::assertSame(
            ['ok', 'warning', 'failed', 'crashed', 'skipped'],
            array_map(static fn (HealthStatus $status) => $status->value, HealthStatus::cases())
        );
    }

    public function testJsonEncodesToValue(): void
    {
        self::assertSame('"warning"', json_encode(HealthStatus::WARNING));
    }
}

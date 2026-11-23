<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Tests\Service;

use PHPUnit\Framework\TestCase;
use Tadpole\OhDear\Enum\HealthStatus;
use Tadpole\OhDear\Service\OhDearHealthReportService;
use Tadpole\OhDear\Struct\HealthCheckStruct;
use Tadpole\OhDear\Tests\HealthCheckStub;

class OhDearHealthReportServiceTest extends TestCase
{
    public function testGetHealthChecksStructsCallsEveryCheckInOrder(): void
    {
        $first = new HealthCheckStub('First', HealthStatus::OK);
        $second = new HealthCheckStub('Second', HealthStatus::FAILED);

        $structs = (new OhDearHealthReportService([$first, $second]))->getHealthChecksStructs();

        self::assertCount(2, $structs);
        self::assertContainsOnlyInstancesOf(HealthCheckStruct::class, $structs);
        self::assertSame(['First', 'Second'], array_map(static fn (HealthCheckStruct $s) => $s->getName(), $structs));
        self::assertSame(1, $first->calls);
        self::assertSame(1, $second->calls);
    }

    public function testAcceptsTaggedIteratorStyleIterable(): void
    {
        $checks = (static function () {
            yield new HealthCheckStub('FromGenerator', HealthStatus::OK);
        })();

        $structs = (new OhDearHealthReportService($checks))->getHealthChecksStructs();

        self::assertSame('FromGenerator', $structs[0]->getName());
    }

    public function testGetHealthChecksArrayShape(): void
    {
        $service = new OhDearHealthReportService([
            new HealthCheckStub('Queue', HealthStatus::WARNING, 'msg', '16 mins', ['queueDuration' => 16]),
            new HealthCheckStub('Tasks', HealthStatus::OK),
        ]);

        $before = time();
        $report = $service->getHealthChecksArray();
        $after = time();

        self::assertSame(['finishedAt', 'checkResults'], array_keys($report));
        self::assertIsInt($report['finishedAt']);
        self::assertGreaterThanOrEqual($before, $report['finishedAt']);
        self::assertLessThanOrEqual($after, $report['finishedAt']);

        self::assertSame([
            [
                'name' => 'Queue',
                'label' => 'Queue label',
                'status' => HealthStatus::WARNING,
                'notificationMessage' => 'msg',
                'shortSummary' => '16 mins',
                'meta' => ['queueDuration' => 16],
            ],
            [
                'name' => 'Tasks',
                'label' => 'Tasks label',
                'status' => HealthStatus::OK,
                'notificationMessage' => '',
                'shortSummary' => '',
                'meta' => [],
            ],
        ], $report['checkResults']);
    }

    /**
     * Oh Dear expects the key; with no checks registered it is an empty list.
     */
    public function testNoChecksGivesEmptyCheckResults(): void
    {
        $report = (new OhDearHealthReportService([]))->getHealthChecksArray();

        self::assertSame(['finishedAt', 'checkResults'], array_keys($report));
        self::assertSame([], $report['checkResults']);
    }
}

<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Attribute\Route;
use Tadpole\OhDear\Controller\OhDearHealthReportRoute;
use Tadpole\OhDear\Enum\HealthStatus;
use Tadpole\OhDear\RouteScope\OhDearRouteScope;
use Tadpole\OhDear\Service\OhDearHealthReportService;
use Tadpole\OhDear\Tests\HealthCheckStub;

class OhDearHealthReportRouteTest extends TestCase
{
    public function testLoadReturnsOhDearJson(): void
    {
        $route = new OhDearHealthReportRoute(new OhDearHealthReportService([
            new HealthCheckStub('QueueSizeCheck', HealthStatus::FAILED, 'Too slow', '61 mins', ['queueDuration' => 61]),
            new HealthCheckStub('ScheduledTasksOverdue', HealthStatus::OK),
        ]));

        $response = $route->load();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));

        $json = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsInt($json['finishedAt']);
        self::assertSame([
            [
                'name' => 'QueueSizeCheck',
                'label' => 'QueueSizeCheck label',
                'status' => 'failed',
                'notificationMessage' => 'Too slow',
                'shortSummary' => '61 mins',
                'meta' => ['queueDuration' => 61],
            ],
            [
                'name' => 'ScheduledTasksOverdue',
                'label' => 'ScheduledTasksOverdue label',
                'status' => 'ok',
                'notificationMessage' => '',
                'shortSummary' => '',
                'meta' => [],
            ],
        ], $json['checkResults']);
    }

    public function testRouteDefinition(): void
    {
        $class = new \ReflectionClass(OhDearHealthReportRoute::class);

        $classRoutes = $class->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);
        self::assertCount(1, $classRoutes);
        self::assertSame(['_routeScope' => [OhDearRouteScope::ID]], $classRoutes[0]->newInstance()->getDefaults());

        $methodRoutes = $class->getMethod('load')->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);
        self::assertCount(1, $methodRoutes);
        $route = $methodRoutes[0]->newInstance();
        self::assertSame('/api/ohdear/health-report', $route->getPath());
        self::assertSame('api.ohdear.healthreport', $route->getName());
        self::assertSame(['GET'], $route->getMethods());
        // no Shopware API auth: access is guarded by the ohdear-api route scope secret instead
        self::assertSame(['auth_required' => false], $route->getDefaults());
    }
}

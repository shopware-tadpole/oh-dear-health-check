<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Tests\Check;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tadpole\OhDear\Check\HealthCheck;
use Tadpole\OhDear\Check\QueueSizeCheck;
use Tadpole\OhDear\Enum\HealthStatus;

class QueueSizeCheckTest extends TestCase
{
    private string $originalTimezone;

    protected function setUp(): void
    {
        $this->originalTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
    }

    private function createCheck(string|false $oldestAvailableAt): QueueSizeCheck
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchOne')
            ->with(self::stringContains('FROM messenger_messages'))
            ->willReturn($oldestAvailableAt);

        return new QueueSizeCheck($connection);
    }

    /**
     * available_at as stored by the doctrine transport: UTC, "Y-m-d H:i:s".
     * The extra 30 seconds keep the floored minute value stable while the test runs.
     */
    private static function utcMinutesAgo(int $minutes): string
    {
        return gmdate('Y-m-d H:i:s', time() - $minutes * 60 - 30);
    }

    public function testImplementsHealthCheck(): void
    {
        self::assertContains(HealthCheck::class, class_implements(QueueSizeCheck::class));
    }

    public function testEmptyQueueIsOk(): void
    {
        $struct = $this->createCheck(false)->getHealthCheckStruct();

        self::assertSame('QueueSizeCheck', $struct->getName());
        self::assertSame('Queue Size Check', $struct->getLabel());
        self::assertSame(HealthStatus::OK, $struct->getStatus());
        self::assertSame('The Queue is empty', $struct->getNotificationMessage());
        self::assertSame('0 mins', $struct->getShortSummary());
        self::assertSame(['queueDuration' => 0], $struct->getMeta());
    }

    public function testUnparseableDateIsTreatedAsEmptyQueue(): void
    {
        $struct = $this->createCheck('not a date')->getHealthCheckStruct();

        self::assertSame(HealthStatus::OK, $struct->getStatus());
        self::assertSame('The Queue is empty', $struct->getNotificationMessage());
    }

    /**
     * @return array<string, array{int, HealthStatus, string}>
     */
    public static function thresholdProvider(): array
    {
        return [
            'fresh message' => [0, HealthStatus::OK, 'The queue is fine'],
            '15 min is still ok' => [15, HealthStatus::OK, 'The queue is fine'],
            '16 min is a warning' => [16, HealthStatus::WARNING, 'The queue takes too long to be processed.'],
            '60 min is still a warning' => [60, HealthStatus::WARNING, 'The queue takes too long to be processed.'],
            '61 min failed' => [61, HealthStatus::FAILED, 'The Queue took longer than 60 minutes to process the last message.'],
            'days old counts days' => [3 * 24 * 60 + 5, HealthStatus::FAILED, 'The Queue took longer than 60 minutes to process the last message.'],
        ];
    }

    #[DataProvider('thresholdProvider')]
    public function testThresholds(int $minutesAgo, HealthStatus $expectedStatus, string $expectedMessage): void
    {
        $struct = $this->createCheck(self::utcMinutesAgo($minutesAgo))->getHealthCheckStruct();

        self::assertSame($expectedStatus, $struct->getStatus());
        self::assertSame($expectedMessage, $struct->getNotificationMessage());
        self::assertSame($minutesAgo . ' mins', $struct->getShortSummary());
        self::assertSame(['queueDuration' => $minutesAgo], $struct->getMeta());
    }

    public function testAvailableAtIsInterpretedAsUtcRegardlessOfPhpTimezone(): void
    {
        date_default_timezone_set('Pacific/Kiritimati'); // UTC+14

        $struct = $this->createCheck(self::utcMinutesAgo(20))->getHealthCheckStruct();

        self::assertSame(HealthStatus::WARNING, $struct->getStatus());
        self::assertSame(['queueDuration' => 20], $struct->getMeta());
    }
}

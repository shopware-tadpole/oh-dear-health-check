<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Tests\Struct;

use PHPUnit\Framework\TestCase;
use Tadpole\OhDear\Enum\HealthStatus;
use Tadpole\OhDear\Struct\HealthCheckStruct;

class HealthCheckStructTest extends TestCase
{
    public function testDefaultsToFailedWithEmptyTexts(): void
    {
        $struct = new HealthCheckStruct();

        self::assertSame(HealthStatus::FAILED, $struct->getStatus());
        self::assertSame('', $struct->getNotificationMessage());
        self::assertSame('', $struct->getShortSummary());
        self::assertSame([], $struct->getMeta());
    }

    public function testSettersAndGetters(): void
    {
        $struct = new HealthCheckStruct();
        $struct->setName('Name');
        $struct->setLabel('Label');
        $struct->setStatus(HealthStatus::WARNING);
        $struct->setNotificationMessage('Message');
        $struct->setShortSummary('Summary');
        $struct->setMeta(['key' => 'value']);

        self::assertSame('Name', $struct->getName());
        self::assertSame('Label', $struct->getLabel());
        self::assertSame(HealthStatus::WARNING, $struct->getStatus());
        self::assertSame('Message', $struct->getNotificationMessage());
        self::assertSame('Summary', $struct->getShortSummary());
        self::assertSame(['key' => 'value'], $struct->getMeta());
    }
}

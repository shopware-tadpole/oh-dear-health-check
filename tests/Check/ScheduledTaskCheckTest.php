<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Tests\Check;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Tadpole\OhDear\Check\ScheduledTaskCheck;
use Tadpole\OhDear\Enum\HealthStatus;

class ScheduledTaskCheckTest extends TestCase
{
    private ?Criteria $capturedCriteria = null;

    private function createCheck(ScheduledTaskEntity ...$tasks): ScheduledTaskCheck
    {
        $collection = new ScheduledTaskCollection($tasks);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('search')
            ->willReturnCallback(function (Criteria $criteria, Context $context) use ($collection) {
                $this->capturedCriteria = $criteria;

                return new EntitySearchResult('scheduled_task', $collection->count(), $collection, null, $criteria, $context);
            });

        return new ScheduledTaskCheck($repository, new ParameterBag());
    }

    private static function task(string $name, string $nextExecution): ScheduledTaskEntity
    {
        $task = new ScheduledTaskEntity();
        $task->setId(md5($name));
        $task->setName($name);
        $task->setScheduledTaskClass('Some\\Missing\\TaskClass');
        $task->setNextExecutionTime(new DateTimeImmutable($nextExecution));

        return $task;
    }

    public function testNoTasksIsOk(): void
    {
        $struct = $this->createCheck()->getHealthCheckStruct();

        self::assertSame('ScheduledTasksOverdue', $struct->getName());
        self::assertSame('Scheduled tasks overdue', $struct->getLabel());
        self::assertSame(HealthStatus::OK, $struct->getStatus());
        self::assertSame('Scheduled tasks are not overdue and running fine.', $struct->getNotificationMessage());
        self::assertSame('Scheduled task running fine.', $struct->getShortSummary());
        self::assertSame(['Scheduled tasks overdue in min' => 0, 'Tasks overdue' => ''], $struct->getMeta());
    }

    public function testOverdueTaskFailsWithMinutesPastTheTenMinuteGracePeriod(): void
    {
        $struct = $this->createCheck(
            self::task('product_export_generate_task', '-30 minutes'),
            self::task('log_entry.cleanup', '-60 minutes'),
        )->getHealthCheckStruct();

        self::assertSame(HealthStatus::FAILED, $struct->getStatus());
        // overdue is measured from the most recent next_execution_time (-30 min) minus the 10 min grace period
        self::assertSame(
            'Scheduled tasks are overdue by 20 min. They might not be running at all or fail while running.',
            $struct->getNotificationMessage()
        );
        self::assertSame('Scheduled task overdue 20 mins', $struct->getShortSummary());
        self::assertSame([
            'Scheduled tasks overdue in min' => 20,
            'Tasks overdue' => "product_export_generate_task\nlog_entry.cleanup",
        ], $struct->getMeta());
    }

    public function testCriteriaExcludesInactiveAndSkippedTasks(): void
    {
        $this->createCheck()->getHealthCheckStruct();

        self::assertNotNull($this->capturedCriteria);
        $filters = $this->capturedCriteria->getFilters();
        self::assertCount(1, $filters);
        self::assertInstanceOf(NotFilter::class, $filters[0]);
        $inner = $filters[0]->getQueries();
        self::assertCount(1, $inner);
        self::assertInstanceOf(EqualsAnyFilter::class, $inner[0]);
        self::assertSame('status', $inner[0]->getField());
        self::assertSame(['inactive', 'skipped'], $inner[0]->getValue());
    }

    public function testTaskNotYetDueIsOk(): void
    {
        $struct = $this->createCheck(self::task('future_task', '+60 minutes'))->getHealthCheckStruct();

        self::assertSame(HealthStatus::OK, $struct->getStatus());
        self::assertSame(0, $struct->getMeta()['Scheduled tasks overdue in min']);
    }

    public function testTaskWithinGracePeriodIsOk(): void
    {
        $struct = $this->createCheck(self::task('just_due', '-5 minutes'))->getHealthCheckStruct();

        self::assertSame(HealthStatus::OK, $struct->getStatus());
    }

    public function testOnlyOverdueTasksAreListed(): void
    {
        $struct = $this->createCheck(
            self::task('overdue_task', '-30 minutes'),
            self::task('future_task', '+60 minutes'),
        )->getHealthCheckStruct();

        self::assertSame(HealthStatus::FAILED, $struct->getStatus());
        self::assertSame('overdue_task', $struct->getMeta()['Tasks overdue']);
        self::assertSame(20, $struct->getMeta()['Scheduled tasks overdue in min']);
    }

    public function testReturnsSameStructInstanceOnRepeatedCalls(): void
    {
        $collection = new ScheduledTaskCollection();
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturnCallback(
            static fn (Criteria $criteria, Context $context) => new EntitySearchResult('scheduled_task', 0, $collection, null, $criteria, $context)
        );
        $check = new ScheduledTaskCheck($repository, new ParameterBag());

        self::assertSame($check->getHealthCheckStruct(), $check->getHealthCheckStruct());
    }
}

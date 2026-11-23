<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Check;

use DateTimeImmutable;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Tadpole\OhDear\Enum\HealthStatus;
use Tadpole\OhDear\Struct\HealthCheckStruct;

use function sprintf;

readonly class ScheduledTaskCheck implements HealthCheck
{
    private const MAX_RUNTIME_BEFORE_WARNING_IN_MINUTES = 10;
    private HealthCheckStruct $statusData;

    public function __construct(
        private EntityRepository $scheduledTaskRepository,
        private ParameterBagInterface $parameterBag
    ) {
        $this->statusData = new HealthCheckStruct();
    }

    public function getHealthCheckStruct(): HealthCheckStruct
    {
        $this->statusData->setName('ScheduledTasksOverdue');
        $this->statusData->setLabel('Scheduled tasks overdue');

        $this->collectData();

        return $this->statusData;
    }

    private function collectData(): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('status', ['inactive', 'skipped']));
        $scheduledTasks = $this->scheduledTaskRepository->search(
            $criteria,
            Context::createDefaultContext()
        )->getEntities();

        $mustRunBefore = (new DateTimeImmutable())->modify(
            sprintf('-%d minutes', self::MAX_RUNTIME_BEFORE_WARNING_IN_MINUTES)
        );
        $this->filterOldScheduledTaskImplementations($scheduledTasks);
        $this->filterTasksWhichRanOnTime($scheduledTasks, $mustRunBefore);

        if ($scheduledTasks->count() === 0) {
            $this->createAllFineResponse();
            return;
        }

        $maxOverdueTime = $this->getMaxOverdueTimestamp($scheduledTasks);

        $this->collectOverdueTasks(
            (int)ceil(($maxOverdueTime - $mustRunBefore->getTimestamp()) / 60) * -1,
            $scheduledTasks
        );
    }

    private function filterOldScheduledTaskImplementations(ScheduledTaskCollection $scheduledTasks): void
    {
        $scheduledTasks->filter(function (ScheduledTaskEntity $task) {
            /** @var class-string<ScheduledTask> $taskClass */
            $taskClass = $task->getScheduledTaskClass();

            // Old Shopware version
            if (!method_exists($taskClass, 'shouldRun')) {
                return true;
            }

            return $taskClass::shouldRun($this->parameterBag);
        });
    }

    /**
     * @param  EntityCollection  $scheduledTasks
     * @param  DateTimeImmutable|false  $mustRunBefore
     * @return void
     */
    private function filterTasksWhichRanOnTime(
        EntityCollection $scheduledTasks,
        DateTimeImmutable|false $mustRunBefore
    ): void {
        $scheduledTasks->filter(static fn(ScheduledTaskEntity $task) => $task->getNextExecutionTime() < $mustRunBefore);
    }

    private function createAllFineResponse(): void
    {
        $this->statusData->setStatus(HealthStatus::OK);
        $this->statusData->setNotificationMessage('Scheduled tasks are not overdue and running fine.');
        $this->statusData->setShortSummary('Scheduled task running fine.');
        $this->statusData->setMeta([
            'Scheduled tasks overdue in min' => 0,
            'Tasks overdue' => ''
        ]);
    }

    /**
     * @param  ScheduledTaskCollection&EntityCollection<ScheduledTaskEntity>  $scheduledTasks
     */
    private function getMaxOverdueTimestamp(ScheduledTaskCollection $scheduledTasks): mixed
    {
        return $scheduledTasks->reduce(function (int $carry, ScheduledTaskEntity $task) {
            return max($carry, $task->getNextExecutionTime()->getTimestamp());
        }, 0);
    }

    private function collectOverdueTasks(int $overDueInMinutes, ScheduledTaskCollection $tasksOverdue): void
    {
        $this->statusData->setStatus(HealthStatus::FAILED);
        $this->statusData->setNotificationMessage(
            "Scheduled tasks are overdue by $overDueInMinutes min. They might not be running at all or fail while running."
        );
        $this->statusData->setShortSummary("Scheduled task overdue $overDueInMinutes mins");
        $this->statusData->setMeta([
            'Scheduled tasks overdue in min' => $overDueInMinutes,
            'Tasks overdue' => implode(
                "\n",
                $tasksOverdue->map(static fn(ScheduledTaskEntity $task) => $task->getName())
            )
        ]);
    }
}

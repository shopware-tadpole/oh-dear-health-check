<?php declare(strict_types=1);

namespace Tadpole\OhDear\Check;

use Doctrine\DBAL\ArrayParameterType;
use Tadpole\OhDear\Struct\HealthCheckStruct;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ScheduledTaskCheck extends AbstractCheck {

    public function __construct(
        private readonly Connection $connection,
        private readonly ParameterBagInterface $parameterBag
    ) {}

    public function getHealthCheckStruct(): HealthCheckStruct
    {
        $queueSizeCheckStruct = new HealthCheckStruct();
        $queueSizeCheckStruct->setName('ScheduledTasksOverdue');
        $queueSizeCheckStruct->setLabel('Scheduled tasks overdue');
        return $this->setScheduledTaskCheckData($queueSizeCheckStruct);
    }

    private function setQueueSizeCheckStruct($queueSizeCheckStruct,$overdue, $maxOverdue): HealthCheckStruct
    {
        if ($overdue <= $maxOverdue) {
            $queueSizeCheckStruct->setStatus('ok');
        } else {
            $queueSizeCheckStruct->setStatus('warning');
        }

        $queueSizeCheckStruct->setNotificationMessage("Scheduled tasks overdue is $overdue min");
        $queueSizeCheckStruct->setShortSummary("$overdue mins");
        $queueSizeCheckStruct->setMeta([
            'Scheduled tasks overdue' => $overdue,
        ]);

        return $queueSizeCheckStruct;
    }

    private function setScheduledTaskCheckData($queueSizeCheckStruct): HealthCheckStruct
    {
        /** @var array{scheduled_task_class: class-string, next_execution_time: string}[] $data */
        $data = $this->connection->createQueryBuilder()
            ->select('s.scheduled_task_class', 's.next_execution_time')
            ->from('scheduled_task', 's')
            ->where('s.status NOT IN(:status)')
            ->setParameter('status', ['inactive', 'skipped'], ArrayParameterType::STRING)
            ->fetchAllAssociative();

        $tasks = array_filter($data, function (array $task) {
            $taskClass = $task['scheduled_task_class'];

            // Old Shopware version
            if (!method_exists($taskClass, 'shouldRun')) {
                return true;
            }

            return $taskClass::shouldRun($this->parameterBag);
        });


        $maxDiff = 10;
        $taskDateLimit = (new \DateTimeImmutable())->modify(\sprintf('-%d minutes', $maxDiff));

        $tasks = array_filter($tasks, fn(array $task) => new \DateTimeImmutable($task['next_execution_time']) < $taskDateLimit);

        if ($tasks === []) {
            return $this->setQueueSizeCheckStruct($queueSizeCheckStruct,0,$maxDiff);
        }

        $maxTaskNextExecTime = 0;

        foreach ($tasks as $task) {
            $maxTaskNextExecTime = max((new \DateTimeImmutable($task['next_execution_time']))->getTimestamp(), $maxTaskNextExecTime);
        }

        $diff = round(abs(
            ($maxTaskNextExecTime - $taskDateLimit->getTimestamp()) / 60
        ));

        return $this->setQueueSizeCheckStruct($queueSizeCheckStruct,$diff,$maxDiff);
    }

}
<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Check;

use DateTime;
use Doctrine\DBAL\Connection;
use Exception;
use Tadpole\OhDear\Enum\HealthStatus;
use Tadpole\OhDear\Struct\HealthCheckStruct;

use function is_string;

readonly class QueueSizeCheck implements HealthCheck
{
    public function __construct(private Connection $connection)
    {
    }

    public function getHealthCheckStruct(): HealthCheckStruct
    {
        $queueSizeCheckStruct = new HealthCheckStruct();
        $queueSizeCheckStruct->setName('QueueSizeCheck');
        $queueSizeCheckStruct->setLabel('Queue Size Check');

        $oldestMessage = $this->getQueueOldestMessage();
        if (!$oldestMessage) {
            $queueSizeCheckStruct->setStatus(HealthStatus::OK);
            $queueSizeCheckStruct->setNotificationMessage('The Queue is empty');
            $queueSizeCheckStruct->setShortSummary('0 mins');
            $queueSizeCheckStruct->setMeta([
                'queueDuration' => 0
            ]);

            return $queueSizeCheckStruct;
        }

        // Get difference between $oldestMessage and now
        $now = new DateTime();
        $diff = $now->diff($oldestMessage);
        $diffInMinutes = $diff->days * 24 * 60 + $diff->h * 60 + $diff->i;

        // If oldest message is older than 30 minutes, set status to warning
        if ($diffInMinutes > 60) {
            $queueSizeCheckStruct->setStatus(HealthStatus::FAILED);
            $queueSizeCheckStruct->setNotificationMessage(
                'The Queue took longer than 60 minutes to process the last message.'
            );
            $queueSizeCheckStruct->setShortSummary($diffInMinutes.' mins');
            $queueSizeCheckStruct->setMeta([
                'queueDuration' => $diffInMinutes
            ]);
        } elseif ($diffInMinutes > 15) {
            $queueSizeCheckStruct->setStatus(HealthStatus::WARNING);
            $queueSizeCheckStruct->setNotificationMessage('The queue takes too long to be processed.');
            $queueSizeCheckStruct->setShortSummary($diffInMinutes.' mins');
            $queueSizeCheckStruct->setMeta([
                'queueDuration' => $diffInMinutes
            ]);
        } else {
            $queueSizeCheckStruct->setStatus(HealthStatus::OK);
            $queueSizeCheckStruct->setNotificationMessage('The queue is fine');
            $queueSizeCheckStruct->setShortSummary($diffInMinutes.' mins');
            $queueSizeCheckStruct->setMeta([
                'queueDuration' => $diffInMinutes
            ]);
        }
        return $queueSizeCheckStruct;
    }

    private function getQueueOldestMessage(): false|DateTime
    {
        /** @var string|false $oldestMessageAt */
        $oldestMessageAt = $this->connection->fetchOne(
            'SELECT available_at FROM messenger_messages WHERE available_at < UTC_TIMESTAMP() ORDER BY available_at ASC LIMIT 1'
        );

        if (is_string($oldestMessageAt)) {
            try {
                return new DateTime($oldestMessageAt.' UTC');
            } catch (Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }
}

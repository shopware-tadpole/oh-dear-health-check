<?php declare(strict_types=1);

namespace Tadpole\OhDear\Service;

use Tadpole\OhDear\Struct\HealthCheckStruct;

class OhDearHealthReportService {

    public function __construct(private readonly iterable $healthChecks)
    {

    }

    public function getHealthChecksStructs(): array
    {
        $HealthCheckStructs = [];

        foreach($this->healthChecks as $healthCheck){
            $HealthCheckStructs[] = $healthCheck->getHealthCheckStruct();
        }

        return $HealthCheckStructs;
    }

    public function getHealthChecksArray(): array {
        $healthChecksStructs = $this->getHealthChecksStructs();

        $healthChecksArray['finishedAt'] = time();

        /** @var HealthCheckStruct $healthChecksStruct */
        foreach($healthChecksStructs as $healthChecksStruct){
            $healthChecksArray['checkResults'][] = [
                'name' => $healthChecksStruct->getName(),
                'label' => $healthChecksStruct->getLabel(),
                'status' => $healthChecksStruct->getStatus(),
                'notificationMessage' => $healthChecksStruct->getNotificationMessage(),
                'shortSummary' => $healthChecksStruct->getShortSummary(),
                'meta' => $healthChecksStruct->getMeta(),
            ];
        }

        return $healthChecksArray;
    }
}
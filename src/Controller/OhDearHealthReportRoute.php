<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Controller;

use Shopware\Core\Framework\Routing\Annotation\Acl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Tadpole\OhDear\Service\OhDearHealthReportService;

#[Route(defaults: ['_routeScope' => ['ohdear-api']])]
class OhDearHealthReportRoute
{
    public function __construct(private readonly OhDearHealthReportService $ohDearHealthReportService)
    {
    }

    #[Route(
        path: '/api/ohdear/health-report',
        name: 'api.ohdear.healthreport',
        defaults: ['auth_required' => false],
        methods: ['GET']
    )]
    public function load(): JsonResponse
    {
        $healthReport = $this->ohDearHealthReportService->getHealthChecksArray();
        return new JsonResponse($healthReport);
    }
}

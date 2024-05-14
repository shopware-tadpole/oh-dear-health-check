<?php declare(strict_types=1);

namespace Tadpole\OhDear\Controller;

use Tadpole\OhDear\Service\OhDearHealthReportService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Util\Json;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;

#[Route(defaults: ['_routeScope' => ['ohdear-api']])]
class OhDearHealthReportRoute
{
    public function __construct(private readonly OhDearHealthReportService $ohDearHealthReportService)
    {
    }

    #[Route(
        path: '/api/ohdear/health-report',
        name: 'api.ohdear.healthreport',
        methods: ['GET'],
        defaults: ['auth_required' => false]
    )]
    public function load(): JsonResponse
    {

        $healthReport = $this->ohDearHealthReportService->getHealthChecksArray();

        return new JsonResponse($healthReport);
    }
}

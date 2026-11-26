<?php

declare(strict_types=1);

namespace Tadpole\OhDear\RouteScope;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\AbstractRouteScope;
use Shopware\Core\Framework\Routing\ApiContextRouteScopeDependant;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;

#[Package('framework')]
class OhDearRouteScope extends AbstractRouteScope implements ApiContextRouteScopeDependant
{
    final public const ID = 'ohdear-api';

    public function __construct(private readonly SystemConfigService $systemConfigService)
    {
    }

    /**
     * @var array<string>
     */
    protected $allowedPaths = ['rest', 'api', 'sw-domain-hash.html'];

    public function isAllowed(Request $request): bool
    {
        $secret = $request->headers->get('oh-dear-health-check-secret');
        $configSecret = $this->systemConfigService->get('TadpoleOhDear.config.ohDearSecret');
        if (!$configSecret || !\is_string($configSecret) || !\is_string($secret)) {
            return false;
        }

        return hash_equals($configSecret, $secret);
    }

    public function getId(): string
    {
        return self::ID;
    }
}

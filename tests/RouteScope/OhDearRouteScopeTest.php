<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Tests\RouteScope;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Routing\ApiContextRouteScopeDependant;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;
use Tadpole\OhDear\RouteScope\OhDearRouteScope;

class OhDearRouteScopeTest extends TestCase
{
    private const HEADER = 'oh-dear-health-check-secret';

    private function createScope(mixed $configuredSecret): OhDearRouteScope
    {
        $config = $this->createStub(SystemConfigService::class);
        $config->method('get')->willReturnCallback(
            static fn (string $key) => $key === 'TadpoleOhDear.config.ohDearSecret' ? $configuredSecret : null
        );

        return new OhDearRouteScope($config);
    }

    private static function request(?string $secret, string $headerName = self::HEADER): Request
    {
        $request = Request::create('/api/ohdear/health-report');
        if ($secret !== null) {
            $request->headers->set($headerName, $secret);
        }

        return $request;
    }

    /**
     * @return array<string, array{mixed, ?string, bool}>
     */
    public static function secretProvider(): array
    {
        return [
            'matching secret' => ['s3cret', 's3cret', true],
            'wrong secret' => ['s3cret', 'nope', false],
            'missing header' => ['s3cret', null, false],
            'empty header' => ['s3cret', '', false],
            'case differs' => ['s3cret', 'S3CRET', false],
            'no secret configured, no header' => [null, null, false],
            'no secret configured, empty header' => ['', '', false],
            'secret "0" counts as not configured' => ['0', '0', false],
        ];
    }

    #[DataProvider('secretProvider')]
    public function testIsAllowed(mixed $configuredSecret, ?string $sentSecret, bool $expected): void
    {
        self::assertSame($expected, $this->createScope($configuredSecret)->isAllowed(self::request($sentSecret)));
    }

    public function testHeaderNameIsCaseInsensitive(): void
    {
        self::assertTrue(
            $this->createScope('s3cret')->isAllowed(self::request('s3cret', 'Oh-Dear-Health-Check-Secret'))
        );
    }

    public function testIdAndPaths(): void
    {
        $scope = $this->createScope('x');

        self::assertSame('ohdear-api', $scope->getId());
        self::assertSame(OhDearRouteScope::ID, $scope->getId());
        self::assertInstanceOf(ApiContextRouteScopeDependant::class, $scope);
        self::assertTrue($scope->isAllowedPath('/api/ohdear/health-report'));
        self::assertFalse($scope->isAllowedPath('/store-api/ohdear/health-report'));
        self::assertSame(['rest', 'api', 'sw-domain-hash.html'], $scope->getRoutePrefixes());
    }
}

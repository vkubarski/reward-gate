<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use RewardGate\Http\Url;

final class UrlTest extends TestCase
{
    protected function tearDown(): void
    {
        Url::configure('', '/index.php');

        parent::tearDown();
    }

    public function testGeneratesUrlsAtDomainRoot(): void
    {
        Url::configure('', '/index.php');

        $this->assertSame(
            '/admin/campaigns',
            Url::to('/admin/campaigns')
        );

        $this->assertSame('/', Url::to('/'));
    }

    public function testGeneratesUrlsInsideSubdirectory(): void
    {
        Url::configure('/demo', '/demo/index.php');

        $this->assertSame(
            '/demo/admin/campaigns',
            Url::to('/admin/campaigns')
        );

        $this->assertSame('/demo/', Url::to('/'));
    }

    public function testAutomaticallyDetectsSubdirectory(): void
    {
        Url::configure(null, '/apps/reward-gate/index.php');

        $this->assertSame(
            '/apps/reward-gate/admin/campaigns',
            Url::to('/admin/campaigns')
        );
    }

    public function testStripsConfiguredBasePath(): void
    {
        Url::configure('/demo', '/demo/index.php');

        $this->assertSame(
            '/admin/campaigns',
            Url::stripBasePath('/demo/admin/campaigns')
        );

        $this->assertSame(
            '/',
            Url::stripBasePath('/demo')
        );
    }

    public function testRejectsPathsOutsideConfiguredBasePath(): void
    {
        Url::configure('/demo', '/demo/index.php');

        $this->assertNull(
            Url::stripBasePath('/admin/campaigns')
        );

        $this->assertNull(
            Url::stripBasePath('/demonstration/admin')
        );
    }
}

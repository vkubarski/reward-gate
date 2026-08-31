<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use RewardGate\Auth\AdminAuthGuard;
use RewardGate\Auth\AdminSessionInterface;

final class AdminAuthGuardTest extends TestCase
{
    public function testProtectCallsHandlerWhenAuthenticated(): void
    {
        $adminSession = $this->createMock(
            AdminSessionInterface::class
        );

        $adminSession
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $called = false;

        $handler = function (array $params) use (
            &$called
        ): void {
            $called = true;
        };

        $guard = new AdminAuthGuard(
            $adminSession
        );

        $protectedHandler = $guard->protect($handler);

        $protectedHandler();

        $this->assertTrue($called);
    }

    public function testProtectPassesParamsToHandler(): void
    {
        $adminSession = $this->createMock(
            AdminSessionInterface::class
        );

        $adminSession
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $receivedParams = null;

        $handler = function (array $params) use (
            &$receivedParams
        ): void {
            $receivedParams = $params;
        };

        $guard = new AdminAuthGuard(
            $adminSession
        );

        $protectedHandler = $guard->protect($handler);

        $params = [
            'id' => '42',
            'section' => 'campaigns',
        ];

        $protectedHandler($params);

        $this->assertSame(
            $params,
            $receivedParams
        );
    }
}

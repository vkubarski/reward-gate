<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use RewardGate\Security\VisitorId;

final class VisitorIdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_COOKIE = [];
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $_COOKIE = [];
        $_SERVER = [];

        parent::tearDown();
    }

    public function testGetGeneratesVisitorIdWhenCookieDoesNotExist(): void
    {
        $visitorId = new VisitorId();

        $result = $visitorId->get();

        $this->assertIsString($result);
        $this->assertSame(64, strlen($result));
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $result
        );

        $this->assertSame(
            $result,
            $_COOKIE['reward_gate_visitor_id']
        );
    }

    public function testGetReturnsExistingValidVisitorId(): void
    {
        $existingVisitorId = str_repeat('a', 64);

        $_COOKIE['reward_gate_visitor_id'] = $existingVisitorId;

        $visitorId = new VisitorId();

        $result = $visitorId->get();

        $this->assertSame(
            $existingVisitorId,
            $result
        );

        $this->assertSame(
            $existingVisitorId,
            $_COOKIE['reward_gate_visitor_id']
        );
    }

    public function testGetGeneratesNewVisitorIdForInvalidCookie(): void
    {
        $_COOKIE['reward_gate_visitor_id'] = 'invalid-visitor-id';

        $visitorId = new VisitorId();

        $result = $visitorId->get();

        $this->assertIsString($result);
        $this->assertSame(64, strlen($result));
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $result
        );

        $this->assertNotSame(
            'invalid-visitor-id',
            $result
        );

        $this->assertSame(
            $result,
            $_COOKIE['reward_gate_visitor_id']
        );
    }

    public function testGetGeneratesNewVisitorIdForWrongLengthCookie(): void
    {
        $_COOKIE['reward_gate_visitor_id'] = str_repeat(
            'a',
            63
        );

        $visitorId = new VisitorId();

        $result = $visitorId->get();

        $this->assertSame(64, strlen($result));
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $result
        );

        $this->assertNotSame(
            str_repeat('a', 63),
            $result
        );
    }

    public function testGetGeneratesNewVisitorIdForUppercaseCookie(): void
    {
        $_COOKIE['reward_gate_visitor_id'] = str_repeat(
            'A',
            64
        );

        $visitorId = new VisitorId();

        $result = $visitorId->get();

        $this->assertSame(64, strlen($result));
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $result
        );

        $this->assertNotSame(
            str_repeat('A', 64),
            $result
        );
    }

    public function testGetCachesVisitorIdForCurrentObject(): void
    {
        $visitorId = new VisitorId();

        $firstResult = $visitorId->get();

        $_COOKIE['reward_gate_visitor_id'] = str_repeat(
            'b',
            64
        );

        $secondResult = $visitorId->get();

        $this->assertSame(
            $firstResult,
            $secondResult
        );
    }

    public function testGetUsesHttpsCookieSettingsWhenRequestIsHttps(): void
    {
        $_SERVER['HTTPS'] = 'on';

        $visitorId = new VisitorId();

        $result = $visitorId->get();

        $this->assertSame(
            $result,
            $_COOKIE['reward_gate_visitor_id']
        );
    }

    public function testGetUsesHttpsCookieSettingsWhenForwardedProtocolIsHttps(): void
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        $visitorId = new VisitorId();

        $result = $visitorId->get();

        $this->assertSame(
            $result,
            $_COOKIE['reward_gate_visitor_id']
        );
    }
}

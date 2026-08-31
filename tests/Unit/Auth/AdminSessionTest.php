<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use RewardGate\Auth\AdminSession;

final class AdminSessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        parent::tearDown();
    }

    public function testIsAuthenticatedReturnsFalseWhenAdminIsNotLoggedIn(): void
    {
        $session = new AdminSession();
        $this->assertFalse($session->isAuthenticated());
    } public function testIsAuthenticatedReturnsTrueWhenAdminIsLoggedIn(): void
    {
        $_SESSION['admin_id'] = 42;
        $session = new AdminSession();
        $this->assertTrue($session->isAuthenticated());
    } public function testGetAdminIdReturnsNullWhenAdminIsNotLoggedIn(): void
    {
        $session = new AdminSession();
        $this->assertNull($session->getAdminId());
    } public function testGetAdminIdReturnsLoggedInAdminId(): void
    {
        $_SESSION['admin_id'] = 42;
        $session = new AdminSession();
        $this->assertSame(42, $session->getAdminId());
    } public function testGetAdminIdCastsStoredValueToInteger(): void
    {
        $_SESSION['admin_id'] = '42';
        $session = new AdminSession();
        $this->assertSame(42, $session->getAdminId());
    } public function testLoginStoresAdminIdInSession(): void
    {
        $session = new AdminSession();
        $session->login(42);
        $this->assertSame(42, $_SESSION['admin_id']);
        $this->assertTrue($session->isAuthenticated());
    } public function testLogoutClearsSession(): void
    {
        $_SESSION = [ 'admin_id' => 42, 'some_other_value' => 'test', ];
        $session = new AdminSession();
        $session->logout();
        $this->assertSame([], $_SESSION);
    }
}

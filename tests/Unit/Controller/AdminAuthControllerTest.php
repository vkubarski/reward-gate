<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use RewardGate\Auth\AdminSessionInterface;
use RewardGate\Controller\AdminAuthController;
use RewardGate\Security\CsrfTokenInterface;
use RewardGate\Service\AdminAuthServiceInterface;
use Throwable;

final class AdminAuthControllerTest extends TestCase
{
    private function createController(AdminAuthServiceInterface $adminAuthService, AdminSessionInterface $adminSession, CsrfTokenInterface $csrfToken): AdminAuthController
    {
        return new AdminAuthController($adminAuthService, $adminSession, $csrfToken);
    } private function captureOutput(callable $callback): string
    {
        ob_start();
        try {
            $callback();
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        } $this->assertIsString($output);
        return $output;
    } public function testShowLoginRendersLoginFormWhenUnauthenticated(): void
    {
        $adminAuthService = $this->createStub(AdminAuthServiceInterface::class);
        $adminSession = $this->createMock(AdminSessionInterface::class);
        $adminSession ->expects($this->once()) ->method('isAuthenticated') ->willReturn(false);
        $csrfToken = $this->createStub(CsrfTokenInterface::class);
        $output = $this->captureOutput(fn () => $this->createController($adminAuthService, $adminSession, $csrfToken)->showLogin());
        $this->assertStringContainsString('<h1>Reward Gate</h1>', $output);
        $this->assertStringContainsString('<h2>Admin Login</h2>', $output);
        $this->assertStringContainsString('name="username"', $output);
        $this->assertStringContainsString('name="password"', $output);
    } public function testLoginRendersErrorWhenCredentialsAreMissing(): void
    {
        $adminAuthService = $this->createMock(AdminAuthServiceInterface::class);
        $adminAuthService ->expects($this->never()) ->method('authenticate');
        $adminSession = $this->createMock(AdminSessionInterface::class);
        $adminSession ->expects($this->once()) ->method('isAuthenticated') ->willReturn(false);
        $csrfToken = $this->createStub(CsrfTokenInterface::class);
        $_POST = [ 'username' => '', 'password' => '', ];
        $output = $this->captureOutput(fn () => $this->createController($adminAuthService, $adminSession, $csrfToken)->login());
        $this->assertStringContainsString('Username and password are required.', $output);
    } public function testLoginTrimsUsernameAndRendersErrorForInvalidCredentials(): void
    {
        $adminAuthService = $this->createMock(AdminAuthServiceInterface::class);
        $adminAuthService ->expects($this->once()) ->method('authenticate') ->with('admin', 'wrong-password') ->willReturn(null);
        $adminSession = $this->createMock(AdminSessionInterface::class);
        $adminSession ->expects($this->once()) ->method('isAuthenticated') ->willReturn(false);
        $csrfToken = $this->createStub(CsrfTokenInterface::class);
        $_POST = [ 'username' => ' admin ', 'password' => 'wrong-password', ];
        $output = $this->captureOutput(fn () => $this->createController($adminAuthService, $adminSession, $csrfToken)->login());
        $this->assertStringContainsString('Invalid username or password.', $output);
    } public function testLogoutReturnsForbiddenForInvalidCsrfToken(): void
    {
        $adminAuthService = $this->createStub(AdminAuthServiceInterface::class);
        $adminSession = $this->createMock(AdminSessionInterface::class);
        $adminSession ->expects($this->never()) ->method('logout');
        $csrfToken = $this->createMock(CsrfTokenInterface::class);
        $csrfToken ->expects($this->once()) ->method('validate') ->with(null) ->willReturn(false);
        $_POST = [];
        http_response_code(200);
        $output = $this->captureOutput(fn () => $this->createController($adminAuthService, $adminSession, $csrfToken)->logout());
        $this->assertSame(403, http_response_code());
        $this->assertSame('Invalid CSRF token', $output);
    }
}

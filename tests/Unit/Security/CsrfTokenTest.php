<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use RewardGate\Security\CsrfToken;

final class CsrfTokenTest extends TestCase
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

    public function testGetGeneratesAndReturnsToken(): void
    {
        $csrfToken = new CsrfToken();

        $token = $csrfToken->get();

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $token
        );
        $this->assertSame(
            $token,
            $_SESSION['csrf_token']
        );
    }

    public function testGetReturnsExistingToken(): void
    {
        $_SESSION['csrf_token'] = 'existing-token';

        $csrfToken = new CsrfToken();

        $token = $csrfToken->get();

        $this->assertSame(
            'existing-token',
            $token
        );
    }

    public function testGetRegeneratesTokenWhenStoredValueIsNotString(): void
    {
        $_SESSION['csrf_token'] = 123;

        $csrfToken = new CsrfToken();

        $token = $csrfToken->get();

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $token
        );
        $this->assertSame(
            $token,
            $_SESSION['csrf_token']
        );
    }

    public function testValidateReturnsTrueForMatchingToken(): void
    {
        $csrfToken = new CsrfToken();

        $token = $csrfToken->get();

        $this->assertTrue(
            $csrfToken->validate($token)
        );
    }

    public function testValidateReturnsFalseForDifferentToken(): void
    {
        $csrfToken = new CsrfToken();

        $csrfToken->get();

        $this->assertFalse(
            $csrfToken->validate('different-token')
        );
    }

    public function testValidateReturnsFalseForNullToken(): void
    {
        $_SESSION['csrf_token'] = 'existing-token';

        $csrfToken = new CsrfToken();

        $this->assertFalse(
            $csrfToken->validate(null)
        );
    }

    public function testValidateReturnsFalseForEmptyToken(): void
    {
        $_SESSION['csrf_token'] = 'existing-token';

        $csrfToken = new CsrfToken();

        $this->assertFalse(
            $csrfToken->validate('')
        );
    }

    public function testValidateReturnsFalseWhenSessionTokenDoesNotExist(): void
    {
        $csrfToken = new CsrfToken();

        $this->assertFalse(
            $csrfToken->validate('some-token')
        );
    }

    public function testValidateReturnsFalseWhenSessionTokenIsNotString(): void
    {
        $_SESSION['csrf_token'] = 123;

        $csrfToken = new CsrfToken();

        $this->assertFalse(
            $csrfToken->validate('123')
        );
    }
}

<?php

declare(strict_types=1);

namespace RewardGate\Controller;

use RewardGate\Auth\AdminSessionInterface;
use RewardGate\Service\AdminAuthServiceInterface;
use RewardGate\Security\CsrfTokenInterface;

final class AdminAuthController
{
    public function __construct(
        private AdminAuthServiceInterface $adminAuthService,
        private AdminSessionInterface $adminSession,
        private CsrfTokenInterface $csrfToken
    ) {
    }

    public function showLogin(): void
    {
        if ($this->adminSession->isAuthenticated()) {
            header('Location: /admin/campaigns');
            exit;
        }

        require __DIR__ . '/../../views/auth/login.php';
    }

    public function login(): void
    {
        if ($this->adminSession->isAuthenticated()) {
            header('Location: /admin/campaigns');
            exit;
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->renderLogin('Username and password are required.');

            return;
        }

        $adminUser = $this->adminAuthService->authenticate(
            $username,
            $password
        );

        if ($adminUser === null) {
            $this->renderLogin('Invalid username or password.');

            return;
        }

        $this->adminSession->login((int)$adminUser['id']);

        header('Location: /admin/campaigns');
        exit;
    }

    public function logout(): void
    {
        if (
            !$this->csrfToken->validate(
                $_POST['csrf_token'] ?? null
            )
        ) {
            http_response_code(403);
            echo 'Invalid CSRF token';

            return;
        }

        $this->adminSession->logout();

        header('Location: /admin/login');
        exit;
    }

    private function renderLogin(?string $error = null): void
    {
        require __DIR__ . '/../../views/auth/login.php';
    }
}

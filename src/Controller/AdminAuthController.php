<?php

declare(strict_types=1);

namespace RewardGate\Controller;

use RewardGate\Auth\AdminSessionInterface;
use RewardGate\Security\CsrfTokenInterface;
use RewardGate\Service\AdminAuthServiceInterface;

final class AdminAuthController
{
    public function __construct(
        private AdminAuthServiceInterface $adminAuthService,
        private AdminSessionInterface $adminSession,
        private CsrfTokenInterface $csrfToken,
        private string $appVersion
    ) {
    }

    public function showLogin(): void
    {
        if ($this->adminSession->isAuthenticated()) {
            header('Location: /admin/campaigns');
            exit;
        }

        $this->renderLogin();
    }

    public function login(): void
    {
        if ($this->adminSession->isAuthenticated()) {
            header('Location: /admin/campaigns');
            exit;
        }

        $username = trim(
            (string)($_POST['username'] ?? '')
        );

        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->renderLogin(
                'Username and password are required.'
            );

            return;
        }

        $adminUser = $this->adminAuthService->authenticate(
            $username,
            $password
        );

        if ($adminUser === null) {
            $this->renderLogin(
                'Invalid username or password.'
            );

            return;
        }

        $this->adminSession->login(
            (int)$adminUser['id']
        );

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

    private function renderLogin(
        ?string $error = null
    ): void {
        $version = $this->appVersion;
        ob_start();

        require __DIR__ . '/../../views/auth/login.php';

        $content = ob_get_clean();
        $title = 'Admin Login - Reward Gate';

        require __DIR__ . '/../../views/layout-auth.php';
    }
}

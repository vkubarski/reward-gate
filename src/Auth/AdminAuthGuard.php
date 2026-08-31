<?php

declare(strict_types=1);

namespace RewardGate\Auth;

final class AdminAuthGuard
{
    public function __construct(
        private AdminSessionInterface $adminSession
    ) {
    }

    public function protect(callable $handler): callable
    {
        return function (array $params = []) use ($handler): void {
            if (!$this->adminSession->isAuthenticated()) {
                header('Location: /admin/login');
                exit;
            }

            $handler($params);
        };
    }
}

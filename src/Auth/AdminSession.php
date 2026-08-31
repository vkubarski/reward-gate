<?php

declare(strict_types=1);

namespace RewardGate\Auth;

final class AdminSession implements AdminSessionInterface
{
    private const SESSION_KEY = 'admin_id';

    public function login(int $adminId): void
    {
        session_regenerate_id(true);

        $_SESSION[self::SESSION_KEY] = $adminId;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    public function getAdminId(): ?int
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return null;
        }

        return (int)$_SESSION[self::SESSION_KEY];
    }
}

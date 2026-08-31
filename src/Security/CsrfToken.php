<?php

declare(strict_types=1);

namespace RewardGate\Security;

final class CsrfToken implements CsrfTokenInterface
{
    private const SESSION_KEY = 'csrf_token';

    public function get(): string
    {
        if (
            !isset($_SESSION[self::SESSION_KEY])
            || !is_string($_SESSION[self::SESSION_KEY])
        ) {
            $_SESSION[self::SESSION_KEY] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public function validate(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($sessionToken)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}

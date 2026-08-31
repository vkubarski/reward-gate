<?php

declare(strict_types=1);

namespace RewardGate\Security;

final class VisitorId implements VisitorIdInterface
{
    private const COOKIE_NAME = 'reward_gate_visitor_id';
    private const TOKEN_BYTES = 32;
    private const COOKIE_LIFETIME = 400 * 24 * 60 * 60;

    private ?string $visitorId = null;

    public function get(): string
    {
        if ($this->visitorId !== null) {
            return $this->visitorId;
        }

        $visitorId = $_COOKIE[self::COOKIE_NAME] ?? null;

        if (
            is_string($visitorId)
            && preg_match('/^[a-f0-9]{64}$/', $visitorId) === 1
        ) {
            $this->visitorId = $visitorId;

            return $visitorId;
        }

        $visitorId = bin2hex(
            random_bytes(self::TOKEN_BYTES)
        );

        $this->setCookie($visitorId);

        // setcookie() only affects the response. It does not
        // update $_COOKIE during the current request.
        $_COOKIE[self::COOKIE_NAME] = $visitorId;

        $this->visitorId = $visitorId;

        return $visitorId;
    }

    private function setCookie(string $visitorId): void
    {
        $isHttps = (
            !empty($_SERVER['HTTPS'])
                && $_SERVER['HTTPS'] !== 'off'
        )
                || ($_SERVER['SERVER_PORT'] ?? null) === '443'
                || strtolower(
                    $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''
                ) === 'https';

        setcookie(
            self::COOKIE_NAME,
            $visitorId,
            [
                        'expires' => time() + self::COOKIE_LIFETIME,
                        'path' => '/',
                        'secure' => $isHttps,
                        'httponly' => true,
                        'samesite' => 'Lax',
                ]
        );
    }
}

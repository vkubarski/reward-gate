<?php

declare(strict_types=1);

namespace RewardGate\Http;

final class Url
{
    private static string $basePath = '';

    public static function configure(
        ?string $basePath,
        string $scriptName
    ): void {
        if ($basePath === null) {
            $scriptPath = parse_url($scriptName, PHP_URL_PATH);

            if (
                !is_string($scriptPath)
                || basename($scriptPath) !== 'index.php'
            ) {
                throw new \RuntimeException(
                    'Unable to detect the application base path.'
                );
            }

            $basePath = dirname($scriptPath);

            if ($basePath === '.' || $basePath === '/') {
                $basePath = '';
            }
        }

        $basePath = trim($basePath);

        if ($basePath === '' || $basePath === '/') {
            self::$basePath = '';
            return;
        }

        if (
            !str_starts_with($basePath, '/')
            || str_contains($basePath, '?')
            || str_contains($basePath, '#')
        ) {
            throw new \InvalidArgumentException(
                'The application base path must be a local URL path.'
            );
        }

        self::$basePath = '/' . trim($basePath, '/');
    }

    public static function basePath(): string
    {
        return self::$basePath;
    }

    public static function to(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');

        if ($path === '/') {
            return self::$basePath . '/';
        }

        return self::$basePath . $path;
    }

    public static function stripBasePath(string $path): ?string
    {
        if (self::$basePath === '') {
            return $path;
        }

        if ($path === self::$basePath) {
            return '/';
        }

        if (!str_starts_with($path, self::$basePath . '/')) {
            return null;
        }

        return substr($path, strlen(self::$basePath));
    }
}

<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

require_once __DIR__ . '/vendor/autoload.php';

$appConfig = require __DIR__ . '/config/app.php';
$dbConfig = require __DIR__ . '/config/database.php';

\RewardGate\Http\Url::configure(
    $appConfig['base_path'] ?? null,
    $_SERVER['SCRIPT_NAME'] ?? '/index.php'
);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (
        !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off'
    )
        || ($_SERVER['SERVER_PORT'] ?? null) === '443'
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => \RewardGate\Http\Url::basePath() ?: '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

$pdo = \RewardGate\Database\Connection::create(
    $dbConfig
);

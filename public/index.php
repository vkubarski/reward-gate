<?php

declare(strict_types=1);

use RewardGate\Http\Router;

require_once __DIR__ . '/../bootstrap.php';
$registerRoutes = require __DIR__ . '/../config/routes.php';

$router = new Router();
$registerRoutes($router);

$path = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

$matched = $router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $path
);

if (
    !$matched
    && is_string($path)
    && (
        $path === '/admin'
        || str_starts_with($path, '/admin/')
    )
) {
    header('Location: /admin/');
    exit;
}

if (!$matched) {
    http_response_code(404);
    echo 'Not Found';
}

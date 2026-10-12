<?php

declare(strict_types=1);

use RewardGate\Http\Router;

require_once __DIR__ . '/../bootstrap.php';
$registerRoutes = require __DIR__ . '/../config/routes.php';

$router = new Router(
    \RewardGate\Http\Url::basePath()
);
$registerRoutes($router);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

$path = parse_url(
    $requestUri,
    PHP_URL_PATH
);

$routePath = is_string($path)
    ? \RewardGate\Http\Url::stripBasePath($path)
    : null;

if ($routePath === null) {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

$matched = $router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $path
);

if (
    !$matched
    && (
        $routePath === '/admin'
        || str_starts_with($routePath, '/admin/')
    )
) {
    header(
        'Location: ' . \RewardGate\Http\Url::to('/admin/')
    );
    exit;
}

if (!$matched) {
    http_response_code(404);
    echo 'Not Found';
}

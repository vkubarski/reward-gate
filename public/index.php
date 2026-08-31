<?php

declare(strict_types=1);

use RewardGate\Http\Router;

require_once __DIR__ . '/../bootstrap.php';
$registerRoutes = require __DIR__ . '/../config/routes.php';

$router = new Router();
$registerRoutes($router);

$router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

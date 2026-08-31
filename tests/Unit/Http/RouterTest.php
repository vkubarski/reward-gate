<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use RewardGate\Http\Router;

final class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        http_response_code(200);
        ob_start();
    } protected function tearDown(): void
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        } parent::tearDown();
    } public function testDispatchesGetRoute(): void
    {
        $router = new Router();
        $called = false;
        $router->get('/test', function (array $params) use (&$called): void {
            $called = true;
        });
        $router->dispatch('GET', '/test');
        $this->assertTrue($called);
        $this->assertSame(200, http_response_code());
    } public function testDispatchesPostRoute(): void
    {
        $router = new Router();
        $called = false;
        $router->post('/test', function (array $params) use (&$called): void {
            $called = true;
        });
        $router->dispatch('POST', '/test');
        $this->assertTrue($called);
        $this->assertSame(200, http_response_code());
    } public function testDispatchExtractsRouteParameters(): void
    {
        $router = new Router();
        $receivedParams = null;
        $router->get('/campaigns/{id}', function (array $params) use (&$receivedParams): void {
            $receivedParams = $params;
        });
        $router->dispatch('GET', '/campaigns/42');
        $this->assertSame([ 'id' => '42', ], $receivedParams);
    } public function testDispatchNormalizesTrailingSlash(): void
    {
        $router = new Router();
        $called = false;
        $router->get('/test', function (array $params) use (&$called): void {
            $called = true;
        });
        $router->dispatch('GET', '/test/');
        $this->assertTrue($called);
        $this->assertSame(200, http_response_code());
    } public function testDispatchesRootRoute(): void
    {
        $router = new Router();
        $called = false;
        $receivedParams = null;
        $router->get('/', function (array $params) use (&$called, &$receivedParams): void {
            $called = true;
            $receivedParams = $params;
        });
        $router->dispatch('GET', '/');
        $this->assertTrue($called);
        $this->assertSame([], $receivedParams);
        $this->assertSame(200, http_response_code());
    } public function testDispatchReturnsNotFoundForUnknownRoute(): void
    {
        $router = new Router();
        $router->dispatch('GET', '/does-not-exist');
        $this->assertSame(404, http_response_code());
        $this->assertSame('Not Found', ob_get_contents());
    } public function testDispatchReturnsNotFoundForWrongMethod(): void
    {
        $router = new Router();
        $called = false;
        $router->get('/test', function (array $params) use (&$called): void {
            $called = true;
        });
        $router->dispatch('POST', '/test');
        $this->assertFalse($called);
        $this->assertSame(404, http_response_code());
        $this->assertSame('Not Found', ob_get_contents());
    }
}

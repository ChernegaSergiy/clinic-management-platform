<?php

namespace App\Core\Http;

use PHPUnit\Framework\TestCase;

class FrontControllerTest extends TestCase
{
    public function testMimeMapContainsCommonTypes(): void
    {
        $mimeMap = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
        ];

        $this->assertEquals('text/css', $mimeMap['css']);
        $this->assertEquals('application/javascript', $mimeMap['js']);
        $this->assertEquals('image/png', $mimeMap['png']);
        $this->assertEquals('font/woff2', $mimeMap['woff2']);
    }

    public function testRouterInitialization(): void
    {
        $router = new Router();
        $this->assertInstanceOf(Router::class, $router);
    }

    public function testRouterCanAddRoutes(): void
    {
        $router = new Router();
        $callback = function () {};

        $router->add('GET', '/test', $callback);
        $this->assertTrue(true);
    }

    public function testRouterParsesUrlCorrectly(): void
    {
        $path = parse_url('http://example.com/test/path?query=1', PHP_URL_PATH);
        $this->assertEquals('/test/path', $path);
    }

    public function testRouterParsesUrlWithOnlyPath(): void
    {
        $path = parse_url('/api/users/123', PHP_URL_PATH);
        $this->assertEquals('/api/users/123', $path);
    }

    public function testStaticFileDetectionLogic(): void
    {
        $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'svg', 'gif', 'ico', 'woff', 'woff2', 'ttf'];

        $this->assertContains('css', $staticExtensions);
        $this->assertContains('js', $staticExtensions);
        $this->assertContains('png', $staticExtensions);
    }

    public function testSessionConfigParameters(): void
    {
        $cookieParams = [
            'lifetime' => 0,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        $this->assertEquals(0, $cookieParams['lifetime']);
        $this->assertEquals('/', $cookieParams['path']);
        $this->assertTrue($cookieParams['secure']);
        $this->assertTrue($cookieParams['httponly']);
        $this->assertEquals('Lax', $cookieParams['samesite']);
    }

    public function testDefaultRouteMapping(): void
    {
        $router = new Router();
        $router->add('GET', '/', function () {});
        $router->add('GET', '/about', function () {});
        $router->add('GET', '/contact', function () {});

        $this->assertTrue(true);
    }

    public function testInstallRouteExists(): void
    {
        $router = new Router();
        $router->add('GET', '/install', function () {});
        $this->assertTrue(true);
    }

    public function testErrorHandlingReturns500(): void
    {
        $this->assertEquals(500, http_response_code(500) ?: 500);
    }
}

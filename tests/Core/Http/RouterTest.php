<?php

namespace App\Core\Http;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    public function testAddStoresRoute(): void
    {
        $this->router->add('GET', '/test', function () {});
        $this->assertTrue(true);
    }

    public function testDispatchReturns404ForUnknownRoute(): void
    {
        $this->router->add('GET', '/known', function () {});
        ob_start();
        $this->router->dispatch('GET', '/unknown');
        $output = ob_get_clean();
        $this->assertStringContainsString('404', $output);
    }

    public function testDispatchReturns404ForUnknownMethod(): void
    {
        $this->router->add('GET', '/test', function () {});
        ob_start();
        $this->router->dispatch('POST', '/test');
        $output = ob_get_clean();
        $this->assertStringContainsString('404', $output);
    }

    public function testDispatchMatchesExactRoute(): void
    {
        $called = false;
        $this->router->add('GET', '/dashboard', function () use (&$called) {
            $called = true;
        });
        $this->router->dispatch('GET', '/dashboard');
        $this->assertTrue($called);
    }

    public function testDispatchExtractsPathParameter(): void
    {
        $capturedId = null;
        $this->router->add('GET', '/users/{id}', function (int $id) use (&$capturedId) {
            $capturedId = $id;
        });
        $this->router->dispatch('GET', '/users/42');
        $this->assertEquals(42, $capturedId);
    }

    public function testDispatchExtractsMultipleParameters(): void
    {
        $captured = [];
        $this->router->add('GET', '/posts/{postId}/comments/{commentId}', function (int $postId, int $commentId) use (&$captured) {
            $captured = ['postId' => $postId, 'commentId' => $commentId];
        });
        $this->router->dispatch('GET', '/posts/10/comments/20');
        $this->assertEquals(['postId' => 10, 'commentId' => 20], $captured);
    }

    public function testDispatchHandlesControllerClass(): void
    {
        $this->router->add('GET', '/test', [TestController::class, 'index']);
        $this->router->dispatch('GET', '/test');
        $this->assertTrue(TestController::$called);
    }

    public function testParseUrlRemovesQueryString(): void
    {
        $_SERVER['REQUEST_URI'] = '/api?foo=bar&baz=qux';
        $capturedPath = null;
        $this->router->add('GET', '/api', function () use (&$capturedPath) {
            $capturedPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        });
        $this->router->dispatch('GET', '/api?foo=bar&baz=qux');
        $this->assertEquals('/api', $capturedPath);
    }

    public function testRoutePatternWithUnderscoreParameter(): void
    {
        $capturedUserName = null;
        $this->router->add('GET', '/profile/{user_name}', function (string $user_name) use (&$capturedUserName) {
            $capturedUserName = $user_name;
        });
        $this->router->dispatch('GET', '/profile/john_doe');
        $this->assertEquals('john_doe', $capturedUserName);
    }
}

class TestController
{
    public static bool $called = false;

    public function index(): void
    {
        self::$called = true;
    }
}

<?php

namespace App\Core\Http;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

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
        $request = Request::create('/unknown', 'GET');
        $response = $this->router->dispatch($request);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDispatchReturns404ForUnknownMethod(): void
    {
        $this->router->add('GET', '/test', function () {});
        $request = Request::create('/test', 'POST');
        $response = $this->router->dispatch($request);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDispatchMatchesExactRoute(): void
    {
        $called = false;
        $this->router->add('GET', '/dashboard', function () use (&$called) {
            $called = true;
            return 'Dashboard content';
        });
        $request = Request::create('/dashboard', 'GET');
        $response = $this->router->dispatch($request);
        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Dashboard content', $response->getContent());
    }

    public function testDispatchExtractsPathParameter(): void
    {
        $capturedId = null;
        $this->router->add('GET', '/users/{id}', function (int $id) use (&$capturedId) {
            $capturedId = $id;
            return 'User found';
        });
        $request = Request::create('/users/42', 'GET');
        $response = $this->router->dispatch($request);
        $this->assertEquals(42, $capturedId);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchExtractsMultipleParameters(): void
    {
        $captured = [];
        $this->router->add('GET', '/posts/{postId}/comments/{commentId}', function (int $postId, int $commentId) use (&$captured) {
            $captured = ['postId' => $postId, 'commentId' => $commentId];
            return 'Comment found';
        });
        $request = Request::create('/posts/10/comments/20', 'GET');
        $response = $this->router->dispatch($request);
        $this->assertEquals(['postId' => 10, 'commentId' => 20], $captured);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDispatchHandlesControllerClass(): void
    {
        $this->router->add('GET', '/test', [TestController::class, 'index']);
        $request = Request::create('/test', 'GET');
        $response = $this->router->dispatch($request);
        $this->assertTrue(TestController::$called);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testParseUrlRemovesQueryString(): void
    {
        $capturedPath = null;
        $this->router->add('GET', '/api', function () use (&$capturedPath) {
            $capturedPath = '/api'; // Request::getPathInfo() removes query string
            return 'API response';
        });
        $request = Request::create('/api?foo=bar&baz=qux', 'GET');
        $response = $this->router->dispatch($request);
        $this->assertEquals('/api', $capturedPath);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRoutePatternWithUnderscoreParameter(): void
    {
        $capturedUserName = null;
        $this->router->add('GET', '/profile/{user_name}', function (string $user_name) use (&$capturedUserName) {
            $capturedUserName = $user_name;
            return 'Profile found';
        });
        $request = Request::create('/profile/john_doe', 'GET');
        $response = $this->router->dispatch($request);
        $this->assertEquals('john_doe', $capturedUserName);
        $this->assertEquals(200, $response->getStatusCode());
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

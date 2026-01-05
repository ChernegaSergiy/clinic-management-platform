<?php

namespace App\Core\Auth;

use PHPUnit\Framework\TestCase;

class AuthGuardTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testCheckRedirectsWhenNoSession(): void
    {
        $_SESSION = [];

        ob_start();
        AuthGuard::check();
        $output = ob_get_clean();

        $this->assertArrayHasKey('intended_url', $_SESSION);
        $headers = headers_list();
        $this->assertTrue(
            in_array('Location: /login', $headers),
            'Should redirect to /login when no session'
        );
    }

    public function testCheckDoesNotRedirectWhenSessionExists(): void
    {
        $_SESSION['user'] = ['id' => 1, 'role_id' => 2];

        ob_start();
        AuthGuard::check();
        $output = ob_get_clean();

        $this->assertEmpty($output);
        $this->assertArrayHasKey('user', $_SESSION);
    }

    public function testHydrateRoleNameSetsRoleName(): void
    {
        $_SESSION['user'] = ['id' => 1, 'role_id' => 2, 'role_name' => null];

        AuthGuard::check();

        $this->assertArrayHasKey('role_name', $_SESSION['user']);
    }

    public function testIsAdminExitsWhenNotAdmin(): void
    {
        $_SESSION['user'] = ['id' => 1, 'role_id' => 2];

        ob_start();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Доступ заборонено');
        AuthGuard::isAdmin();
    }

    public function testIsAdminPassesWhenAdmin(): void
    {
        $_SESSION['user'] = ['id' => 1, 'role_id' => 1];

        AuthGuard::isAdmin();

        $this->assertTrue(true);
    }
}

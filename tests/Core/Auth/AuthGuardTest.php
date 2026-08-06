<?php

namespace App\Core\Auth;

use App\Core\Exception\RedirectException;
use App\Module\User\Repository\RoleRepository;
use PHPUnit\Framework\TestCase;

class AuthGuardTest extends TestCase
{
    protected function setUp() : void
    {
        $_SESSION = [];

        $mockRoleRepo = $this->createMock(RoleRepository::class);
        $mockRoleRepo->method('findById')->willReturn(['name' => 'Admin']);
        AuthGuard::setRoleRepository($mockRoleRepo);
    }

    protected function tearDown() : void
    {
        $_SESSION = [];
        AuthGuard::resetRoleRepository();
    }

    public function testCheckRedirectsWhenNoSession() : void
    {
        $_SESSION = [];

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('Redirect to: /login');

        AuthGuard::check();

        $this->assertArrayHasKey('intended_url', $_SESSION);
    }

    public function testCheckDoesNotRedirectWhenSessionExists() : void
    {
        $_SESSION['user'] = ['id' => 1, 'role_id' => 2];

        AuthGuard::check();

        $this->assertArrayHasKey('user', $_SESSION);
    }

    public function testHydrateRoleNameSetsRoleName() : void
    {
        $_SESSION['user'] = ['id' => 1, 'role_id' => 2, 'role_name' => null];

        AuthGuard::check();

        $this->assertArrayHasKey('role_name', $_SESSION['user']);
    }

    public function testIsAdminExitsWhenNotAdmin() : void
    {
        $_SESSION['user'] = ['id' => 1, 'role_id' => 2];

        $this->expectException(\App\Core\Exception\ExitException::class);
        $this->expectExceptionMessage('Доступ заборонено');

        AuthGuard::isAdmin();
    }

    public function testIsAdminPassesWhenAdmin() : void
    {
        $_SESSION['user'] = ['id' => 1, 'role_id' => 1];

        AuthGuard::isAdmin();

        $this->assertTrue(true);
    }
}

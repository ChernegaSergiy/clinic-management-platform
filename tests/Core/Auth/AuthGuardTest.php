<?php

namespace App\Core\Auth;

use App\Bundles\UserBundle\Repository\RoleRepositoryInterface;
use App\Core\Exception\ExitException;
use App\Core\Exception\RedirectException;
use PHPUnit\Framework\TestCase;

class AuthGuardTest extends TestCase
{
    private AuthGuard $authGuard;
    private $mockRoleRepo;
    private $mockMfaGuard;

    protected function setUp() : void
    {
        $_SESSION = [];

        $this->mockRoleRepo = $this->createMock(RoleRepositoryInterface::class);
        $this->mockRoleRepo->method('findById')->willReturn(['name' => 'Admin']);

        $this->mockMfaGuard = $this->createMock(MfaGuard::class);

        $this->authGuard = new AuthGuard($this->mockRoleRepo, $this->mockMfaGuard);
    }

    protected function tearDown() : void
    {
        $_SESSION = [];
    }

    public function testCheckRedirectsWhenNoSession() : void
    {
        $_SESSION = [];

        // AuthStep::current() relies on $_SESSION to determine step, so it will be unauthorized.
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('Redirect to: /login');

        $this->authGuard->check();

        $this->assertArrayHasKey('intended_url', $_SESSION);
    }

    public function testCheckDoesNotRedirectWhenSessionExists() : void
    {
        $_SESSION['user'] = ['id' => 1, 'role_id' => 2];
        // Set MFA verify to passed
        $_SESSION['mfa_verified'] = true;
        // Or make sure AuthStep resolves to 'authorized'
        $_SESSION['user_id'] = 1;

        $this->authGuard->check();

        $this->assertArrayHasKey('user', $_SESSION);
    }

    public function testHydrateRoleNameSetsRoleName() : void
    {
        $_SESSION['user'] = ['id' => 1, 'role_id' => 2, 'role_name' => null];
        $_SESSION['mfa_verified'] = true;

        $this->authGuard->check();

        $this->assertArrayHasKey('role_name', $_SESSION['user']);
        $this->assertEquals('Admin', $_SESSION['user']['role_name']);
    }

    public function testIsAdminExitsWhenNotAdmin() : void
    {
        $_SESSION['user'] = ['id' => 1, 'role_id' => 2];
        $_SESSION['mfa_verified'] = true;

        $this->expectException(ExitException::class);
        $this->expectExceptionMessage('Доступ заборонено');

        $this->authGuard->isAdmin();
    }

    public function testIsAdminPassesWhenAdmin() : void
    {
        $_SESSION['user'] = ['id' => 1, 'role_id' => 1];
        $_SESSION['mfa_verified'] = true;

        $this->authGuard->isAdmin();

        $this->assertTrue(true);
    }
}

<?php

namespace App\Core\Auth;

use App\Core\Exception\ExitException;
use App\Core\Model\User;
use PHPUnit\Framework\TestCase;

class GateTest extends TestCase
{
    private PermissionRegistry $permissionRegistry;
    private PolicyRegistry $policyRegistry;
    private Gate $gate;

    protected function setUp(): void
    {
        $this->permissionRegistry = new PermissionRegistry();
        $this->policyRegistry = new PolicyRegistry();
        Gate::setPermissionRegistry($this->permissionRegistry);
        Gate::setPolicyRegistry($this->policyRegistry);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testGetUserReturnsNullWhenNoSession(): void
    {
        $result = Gate::getUser();
        $this->assertNull($result);
    }

    public function testGetUserReturnsUserFromSession(): void
    {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 2,
            'role_name' => 'doctor',
            'permissions' => ['patient.view']
        ];

        $user = Gate::getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->getId());
    }

    public function testAllowsReturnsFalseWhenNoUser(): void
    {
        $result = Gate::allows('patient.view');
        $this->assertFalse($result);
    }

    public function testAllowsReturnsTrueForAdmin(): void
    {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 1,
            'role_name' => 'admin',
            'permissions' => []
        ];

        $result = Gate::allows('anything');
        $this->assertTrue($result);
    }

    public function testAllowsChecksPermission(): void
    {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 2,
            'role_name' => 'doctor',
            'permissions' => ['patient.view', 'patient.edit']
        ];

        $this->assertTrue(Gate::allows('patient.view'));
        $this->assertTrue(Gate::allows('patient.edit'));
        $this->assertFalse(Gate::allows('patient.delete'));
        $this->assertFalse(Gate::allows('admin.system'));
    }

    public function testAllowsChecksGranularPermission(): void
    {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 2,
            'role_name' => 'doctor',
            'permissions' => ['prescription.create_own', 'prescription.edit_own', 'prescription.view_any']
        ];

        $this->assertTrue(Gate::allows('prescription.create_own'));
        $this->assertTrue(Gate::allows('prescription.edit_own'));
        $this->assertTrue(Gate::allows('prescription.view_any'));
        $this->assertFalse(Gate::allows('prescription.delete_any'));
    }

    public function testAuthorizeExitsOnNoUser(): void
    {
        $_SESSION = [];

        $this->expectException(ExitException::class);
        $this->expectExceptionMessage('Доступ заборонено (не автентифіковано)');

        Gate::authorize('patient.view');
    }

    public function testAuthorizeExitsOnNoPermission(): void
    {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 2,
            'role_name' => 'doctor',
            'permissions' => []
        ];

        $this->expectException(ExitException::class);
        $this->expectExceptionMessage('Доступ заборонено');

        Gate::authorize('patient.delete');
    }

    public function testAuthorizePassesWithPermission(): void
    {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 2,
            'role_name' => 'doctor',
            'permissions' => ['patient.view']
        ];

        Gate::authorize('patient.view');
        $this->assertTrue(true);
    }
}

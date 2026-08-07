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

    protected function setUp() : void
    {
        $this->permissionRegistry = new PermissionRegistry();
        $this->policyRegistry = new PolicyRegistry();

        $this->gate = new Gate($this->permissionRegistry, $this->policyRegistry);
    }

    protected function tearDown() : void
    {
        $_SESSION = [];
    }

    public function testGetUserReturnsNullWhenNoSession() : void
    {
        $result = $this->gate->getUser();
        $this->assertNull($result);
    }

    public function testGetUserReturnsUserFromSession() : void
    {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 2,
            'role_name' => 'doctor',
            'permissions' => ['patient.view']
        ];

        $user = $this->gate->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->getId());
    }

    public function testAllowsReturnsFalseWhenNoUser() : void
    {
        $result = $this->gate->allows('patient.view');
        $this->assertFalse($result);
    }

    public function testAllowsReturnsTrueForAdmin() : void
    {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 1,
            'role_name' => 'admin',
            'permissions' => []
        ];

        $result = $this->gate->allows('anything');
        $this->assertTrue($result);
    }

    public function testAllowsChecksPermission() : void
    {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 2,
            'role_name' => 'doctor',
            'permissions' => ['patient.view', 'patient.edit']
        ];

        $this->assertTrue($this->gate->allows('patient.view'));
        $this->assertTrue($this->gate->allows('patient.edit'));
        $this->assertFalse($this->gate->allows('patient.delete'));
        $this->assertFalse($this->gate->allows('admin.system'));
    }

    public function testAllowsChecksGranularPermission() : void
    {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 2,
            'role_name' => 'doctor',
            'permissions' => ['prescription.create_own', 'prescription.edit_own', 'prescription.view_any']
        ];

        $this->assertTrue($this->gate->allows('prescription.create_own'));
        $this->assertTrue($this->gate->allows('prescription.edit_own'));
        $this->assertTrue($this->gate->allows('prescription.view_any'));
        $this->assertFalse($this->gate->allows('prescription.delete_any'));
    }

    public function testAuthorizeExitsOnNoUser() : void
    {
        $_SESSION = [];

        $this->expectException(ExitException::class);
        $this->expectExceptionMessage('Доступ заборонено (не автентифіковано)');

        $this->gate->authorize('patient.view');
    }

    public function testAuthorizeExitsOnNoPermission() : void
    {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 2,
            'role_name' => 'doctor',
            'permissions' => []
        ];

        $this->expectException(ExitException::class);
        $this->expectExceptionMessage('Доступ заборонено');

        $this->gate->authorize('patient.delete');
    }

    public function testAuthorizePassesWithPermission() : void
    {
        $_SESSION['user'] = [
            'id' => 1,
            'role_id' => 2,
            'role_name' => 'doctor',
            'permissions' => ['patient.view']
        ];

        $this->gate->authorize('patient.view');
        $this->assertTrue(true);
    }
}

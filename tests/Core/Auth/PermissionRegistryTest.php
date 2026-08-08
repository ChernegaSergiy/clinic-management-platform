<?php

namespace App\Tests\Core\Auth;

use App\Core\Auth\PermissionRegistry;
use PHPUnit\Framework\TestCase;

class PermissionRegistryTest extends TestCase
{
    public function testAddRegistersPermissionWithoutDescription() : void
    {
        $registry = new PermissionRegistry();

        $registry->add('patient.read');

        $this->assertTrue($registry->hasPermission('patient.read'));
        $this->assertSame(['patient.read'], $registry->getPermissions());
        $this->assertNull($registry->getPermissionDescription('patient.read'));
    }

    public function testAddRegistersPermissionWithDescription() : void
    {
        $registry = new PermissionRegistry();

        $registry->add('patient.write', 'Create and edit patients');

        $this->assertSame('Create and edit patients', $registry->getPermissionDescription('patient.write'));
    }

    public function testHasPermissionReturnsFalseForUnknownPermission() : void
    {
        $registry = new PermissionRegistry();

        $this->assertFalse($registry->hasPermission('unknown.permission'));
    }

    public function testAddRoleMappingAssociatesPermissionsWithRole() : void
    {
        $registry = new PermissionRegistry();

        $registry->addRoleMapping('doctor', ['patient.read', 'patient.write']);

        $this->assertSame(['patient.read', 'patient.write'], $registry->getRolePermissions('doctor'));
    }

    public function testAddRoleMappingMergesWithExistingPermissions() : void
    {
        $registry = new PermissionRegistry();

        $registry->addRoleMapping('doctor', ['patient.read']);
        $registry->addRoleMapping('doctor', ['patient.write']);

        $this->assertSame(['patient.read', 'patient.write'], $registry->getRolePermissions('doctor'));
    }

    public function testGetRolePermissionsReturnsEmptyArrayForUnknownRole() : void
    {
        $registry = new PermissionRegistry();

        $this->assertSame([], $registry->getRolePermissions('unknown-role'));
    }

    public function testClearResetsAllRegisteredData() : void
    {
        $registry = new PermissionRegistry();
        $registry->add('patient.read', 'Read patients');
        $registry->addRoleMapping('doctor', ['patient.read']);

        $registry->clear();

        $this->assertSame([], $registry->getPermissions());
        $this->assertFalse($registry->hasPermission('patient.read'));
        $this->assertSame([], $registry->getRolePermissions('doctor'));
        $this->assertNull($registry->getPermissionDescription('patient.read'));
    }
}

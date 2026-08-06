<?php

namespace App\Core\Auth;

class PermissionRegistry
{
    private array $permissions = [];
    private array $rolePermissions = [];
    private array $permissionDescriptions = [];

    public function add(string $permission, string $description = '') : void
    {
        $this->permissions[$permission] = true;
        if ($description) {
            $this->permissionDescriptions[$permission] = $description;
        }
    }

    public function addRoleMapping(string $role, array $permissions) : void
    {
        if (!isset($this->rolePermissions[$role])) {
            $this->rolePermissions[$role] = [];
        }
        $this->rolePermissions[$role] = array_merge($this->rolePermissions[$role], $permissions);
    }

    public function getPermissions() : array
    {
        return array_keys($this->permissions);
    }

    public function getRolePermissions(string $role) : array
    {
        return $this->rolePermissions[$role] ?? [];
    }

    public function hasPermission(string $permission) : bool
    {
        return isset($this->permissions[$permission]);
    }

    public function getPermissionDescription(string $permission) : ?string
    {
        return $this->permissionDescriptions[$permission] ?? null;
    }

    public function clear() : void
    {
        $this->permissions = [];
        $this->rolePermissions = [];
        $this->permissionDescriptions = [];
    }
}

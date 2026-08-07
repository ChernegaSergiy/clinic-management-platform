<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

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

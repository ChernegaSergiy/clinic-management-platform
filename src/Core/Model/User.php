<?php

namespace App\Core\Model;

class User
{
    private array $data;
    private array $permissions;

    public function __construct(array $data, array $permissions)
    {
        $this->data = $data;
        $this->permissions = $permissions;
    }

    public function getId() : ?int
    {
        return $this->data['id'] ?? null;
    }

    public function getRole() : string
    {
        return $this->data['role_name'] ?? '';
    }

    public function isAdmin() : bool
    {
        return 'admin' === $this->getRole();
    }

    public function hasPermission(string $permission) : bool
    {
        return in_array($permission, $this->permissions) || in_array('*', $this->permissions);
    }
}

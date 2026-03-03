<?php

namespace App\Module\Department;

use App\Core\Auth\Policy;
use App\Core\Model\User;

class DepartmentPolicy implements Policy
{
    public function view(User $user, array $context): bool
    {
        return $user->hasPermission('department.read');
    }

    public function create(User $user, array $context): bool
    {
        return $user->hasPermission('department.write');
    }

    public function update(User $user, array $context): bool
    {
        return $user->hasPermission('department.write');
    }

    public function delete(User $user, array $context): bool
    {
        return $user->hasPermission('department.delete');
    }
}

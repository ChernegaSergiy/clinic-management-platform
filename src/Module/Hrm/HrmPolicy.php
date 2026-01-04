<?php

namespace App\Module\Hrm;

use App\Core\Policy;
use App\Core\User;

class HrmPolicy implements Policy
{
    public function view(User $user, array $context): bool
    {
        return $user->hasPermission('hrm.read');
    }

    public function create(User $user, array $context): bool
    {
        return $user->hasPermission('hrm.write');
    }

    public function update(User $user, array $context): bool
    {
        return $user->hasPermission('hrm.write');
    }

    public function delete(User $user, array $context): bool
    {
        return $user->hasPermission('hrm.manage');
    }
}

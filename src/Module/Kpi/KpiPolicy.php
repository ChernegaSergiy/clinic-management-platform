<?php

namespace App\Module\Kpi;

use App\Core\Policy;
use App\Core\User;

class KpiPolicy implements Policy
{
    public function view(User $user, array $context): bool
    {
        return $user->hasPermission('kpi.read');
    }

    public function create(User $user, array $context): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, array $context): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, array $context): bool
    {
        return $user->isAdmin();
    }
}

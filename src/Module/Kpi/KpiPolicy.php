<?php

namespace App\Module\Kpi;

use App\Core\Auth\Policy;
use App\Core\Model\User;

class KpiPolicy implements Policy
{
    public function view(User $user, array $context) : bool
    {
        return $user->hasPermission('kpi.read');
    }

    public function create(User $user, array $context) : bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, array $context) : bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, array $context) : bool
    {
        return $user->isAdmin();
    }
}

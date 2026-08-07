<?php

namespace App\Bundles\DashboardBundle;

use App\Core\Auth\Policy;
use App\Core\Model\User;

class DashboardPolicy implements Policy
{
    public function view(User $user, array $context) : bool
    {
        return $user->hasPermission('dashboard.view');
    }

    public function export(User $user, array $context) : bool
    {
        return $user->hasPermission('dashboard.export');
    }
}

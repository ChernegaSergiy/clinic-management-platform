<?php

namespace App\Module\Dashboard;

use App\Core\Policy;
use App\Core\User;

class DashboardPolicy implements Policy
{
    public function view(User $user, array $context): bool
    {
        return $user->hasPermission('dashboard.view');
    }

    public function export(User $user, array $context): bool
    {
        return $user->hasPermission('dashboard.export');
    }
}
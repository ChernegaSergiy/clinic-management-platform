<?php

namespace App\Module\Billing;

use App\Core\Policy;
use App\Core\User;

class BillingPolicy implements Policy
{
    public function view(User $user, array $context): bool
    {
        return $user->hasPermission('billing.read');
    }

    public function create(User $user, array $context): bool
    {
        return $user->hasPermission('billing.manage');
    }

    public function update(User $user, array $context): bool
    {
        return $user->hasPermission('billing.manage');
    }

    public function delete(User $user, array $context): bool
    {
        return $user->hasPermission('billing.manage');
    }
}
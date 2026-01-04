<?php

namespace App\Module\Insurance;

use App\Core\Policy;
use App\Core\User;

class InsurancePolicy implements Policy
{
    public function manage(User $user, array $context): bool
    {
        return $user->hasPermission('insurance.manage');
    }
}
<?php

namespace App\Module\Insurance;

use App\Core\Auth\Policy;
use App\Core\Model\User;

class InsurancePolicy implements Policy
{
    public function manage(User $user, array $context): bool
    {
        return $user->hasPermission('insurance.manage');
    }
}

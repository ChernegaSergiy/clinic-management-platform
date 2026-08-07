<?php

namespace App\Bundles\InsuranceBundle;

use App\Core\Auth\Policy;
use App\Core\Model\User;

class InsurancePolicy implements Policy
{
    public function manage(User $user, array $context) : bool
    {
        return $user->hasPermission('insurance.manage');
    }
}

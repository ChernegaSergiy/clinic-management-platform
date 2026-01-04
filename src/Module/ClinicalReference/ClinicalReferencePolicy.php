<?php

namespace App\Module\ClinicalReference;

use App\Core\Auth\Policy;
use App\Core\Model\User;

class ClinicalReferencePolicy implements Policy
{
    public function manage(User $user, array $context): bool
    {
        return $user->hasPermission('clinical.manage');
    }
}

<?php

namespace App\Module\ClinicalReference;

use App\Core\Policy;
use App\Core\User;

class ClinicalReferencePolicy implements Policy
{
    public function manage(User $user, array $context): bool
    {
        return $user->hasPermission('clinical.manage');
    }
}
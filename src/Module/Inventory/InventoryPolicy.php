<?php

namespace App\Module\Inventory;

use App\Core\Auth\Policy;
use App\Core\Model\User;

class InventoryPolicy implements Policy
{
    public function manage(User $user, array $context): bool
    {
        return $user->hasPermission('inventory.manage');
    }
}

<?php

namespace App\Module\Inventory;

use App\Core\Policy;
use App\Core\User;

class InventoryPolicy implements Policy
{
    public function manage(User $user, array $context): bool
    {
        return $user->hasPermission('inventory.manage');
    }
}
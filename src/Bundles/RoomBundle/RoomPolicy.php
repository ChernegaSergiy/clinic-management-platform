<?php

namespace App\Bundles\RoomBundle;

use App\Core\Auth\Policy;
use App\Core\Model\User;

class RoomPolicy implements Policy
{
    public function manage(User $user, array $context) : bool
    {
        return $user->hasPermission('rooms.manage');
    }
}

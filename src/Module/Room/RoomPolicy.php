<?php

namespace App\Module\Room;

use App\Core\Policy;
use App\Core\User;

class RoomPolicy implements Policy
{
    public function manage(User $user, array $context): bool
    {
        return $user->hasPermission('rooms.manage');
    }
}
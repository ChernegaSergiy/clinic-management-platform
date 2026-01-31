<?php

namespace App\Event;

use App\Core\Model\User;
use Symfony\Component\EventDispatcher\Event;

class UserLoggedInEvent extends Event
{
    public function __construct(
        public readonly User $user
    ) {}
}
<?php

namespace App\Event;

use App\Core\Model\User;
use Symfony\Contracts\EventDispatcher\Event;

class EntityChangedEvent extends Event
{
    public function __construct(
        public readonly object $entity,
        public readonly string $action, // 'created', 'updated', 'deleted'
        public readonly ?User $user = null
    ) {}
}
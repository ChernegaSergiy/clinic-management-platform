<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EntityChangedEvent extends Event
{
    public function __construct(
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly string $action, // 'create', 'update', 'delete'
        public readonly mixed $oldData = null,
        public readonly mixed $newData = null
    ) {}
}

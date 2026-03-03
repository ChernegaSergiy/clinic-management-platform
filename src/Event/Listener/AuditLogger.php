<?php

namespace App\Event\Listener;

use App\Event\EntityChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuditLogger implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            EntityChangedEvent::class => 'onEntityChanged',
        ];
    }

    public function onEntityChanged(EntityChangedEvent $event): void
    {
        // Log the change
        $entityType = $event->entityType;
        $action = $event->action;
        $entityId = $event->entityId;

        // Example: write to log file or database
        error_log(sprintf(
            'Entity %s#%d %s',
            $entityType,
            $entityId,
            $action
        ));
    }
}

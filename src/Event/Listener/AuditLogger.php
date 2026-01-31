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
        $entity = $event->entity;
        $action = $event->action;
        $user = $event->user;

        // Example: write to log file or database
        error_log(sprintf(
            'Entity %s %s by user %s',
            get_class($entity),
            $action,
            $user ? $user->getId() : 'unknown'
        ));
    }
}
<?php

namespace App\Event\Listener;

use App\Event\NotificationRequestedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailNotifier implements EventSubscriberInterface
{
    public static function getSubscribedEvents() : array
    {
        return [
            NotificationRequestedEvent::class => 'onNotificationRequested',
        ];
    }

    public function onNotificationRequested(NotificationRequestedEvent $event) : void
    {
        // Send email
        $type = $event->type;
        $recipient = $event->recipient;
        $message = $event->message;

        if ('email' === $type) {
            // Use mail() or a service to send email
            mail($recipient, 'Notification', $message);
        }
    }
}

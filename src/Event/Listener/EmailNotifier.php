<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

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

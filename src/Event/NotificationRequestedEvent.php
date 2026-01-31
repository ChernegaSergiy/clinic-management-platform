<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class NotificationRequestedEvent extends Event
{
    public function __construct(
        public readonly string $type, // 'email', 'sms', etc.
        public readonly string $recipient,
        public readonly string $message
    ) {}
}
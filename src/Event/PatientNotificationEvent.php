<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class PatientNotificationEvent extends Event
{
    public function __construct(
        public readonly int $patientId,
        public readonly string $type,
        public readonly string $message,
        public readonly ?array $context = null
    ) {}
}

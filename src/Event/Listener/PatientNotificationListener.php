<?php

namespace App\Event\Listener;

use App\Event\PatientNotificationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PatientNotificationListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PatientNotificationEvent::class => 'onPatientNotification',
        ];
    }

    public function onPatientNotification(PatientNotificationEvent $event): void
    {
        // Логування сповіщення
        error_log(sprintf(
            '[PATIENT_NOTIFICATION] Patient ID: %d, Type: %s, Message: %s',
            $event->patientId,
            $event->type,
            $event->message
        ));

        // Тут можна додати логіку відправки email, SMS тощо
        // Наприклад:
        // $this->emailService->sendNotification($event->patientId, $event->type, $event->message);
        // $this->smsService->sendNotification($event->patientId, $event->message);
    }
}

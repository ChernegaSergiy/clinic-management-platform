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

use App\Event\PatientNotificationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PatientNotificationListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents() : array
    {
        return [
            PatientNotificationEvent::class => 'onPatientNotification',
        ];
    }

    public function onPatientNotification(PatientNotificationEvent $event) : void
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

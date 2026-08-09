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

namespace App\Bundles\AppointmentBundle;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepositoryInterface;
use App\Core\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AppointmentVoter extends Voter
{
    private AppointmentRepositoryInterface $appointmentRepository;

    public function __construct(AppointmentRepositoryInterface $appointmentRepository)
    {
        $this->appointmentRepository = $appointmentRepository;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, ['ROLE_APPOINTMENT_VIEW_OWN', 'ROLE_APPOINTMENT_EDIT_OWN', 'ROLE_APPOINTMENT_CANCEL_OWN']);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $context = is_array($subject) ? $subject : [];

        switch ($attribute) {
            case 'ROLE_APPOINTMENT_VIEW_OWN':
                $appointmentId = $context['id'] ?? null;
                if (!$appointmentId) {
                    return false;
                }

                return $this->isUserOwnerOfAppointment($user, (int)$appointmentId);
            case 'ROLE_APPOINTMENT_EDIT_OWN':
            case 'ROLE_APPOINTMENT_CANCEL_OWN':
                $appointmentId = $context['id'] ?? null;
                if (!$appointmentId) {
                    return false;
                }

                return $this->isUserOwnerOfAppointment($user, (int)$appointmentId);
        }

        return false;
    }

    private function isUserOwnerOfAppointment(User $user, int $appointmentId) : bool
    {
        $userId = $user->getId();
        if (!$userId) {
            return false;
        }
        return $this->appointmentRepository->isAppointmentOwnedByDoctor($appointmentId, $userId);
    }
}

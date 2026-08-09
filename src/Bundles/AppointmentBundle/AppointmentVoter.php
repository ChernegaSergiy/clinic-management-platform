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
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AppointmentVoter extends Voter
{
    public const VIEW = 'APPOINTMENT_VIEW';
    public const EDIT = 'APPOINTMENT_EDIT';
    public const CANCEL = 'APPOINTMENT_CANCEL';

    private AppointmentRepositoryInterface $appointmentRepository;
    private Security $security;

    public function __construct(
        AppointmentRepositoryInterface $appointmentRepository,
        Security $security
    ) {
        $this->appointmentRepository = $appointmentRepository;
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::CANCEL], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Administrators and Medical Managers have full access
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MEDICAL_MANAGER')) {
            return true;
        }

        $appointmentId = $this->extractAppointmentId($subject);

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($user, $appointmentId);
            case self::EDIT:
                return $this->canEdit($user, $appointmentId);
            case self::CANCEL:
                return $this->canCancel($user, $appointmentId);
        }

        return false;
    }

    private function canView(User $user, ?int $appointmentId) : bool
    {
        // Registrars can view all appointments
        if ($this->security->isGranted('ROLE_REGISTRAR')) {
            return true;
        }

        // Doctors and nurses can view their own assigned appointments
        if ($appointmentId && ($this->security->isGranted('ROLE_DOCTOR') || $this->security->isGranted('ROLE_NURSE'))) {
            return $this->isUserOwnerOfAppointment($user, $appointmentId);
        }

        return false;
    }

    private function canEdit(User $user, ?int $appointmentId) : bool
    {
        // Registrars can edit any appointment
        if ($this->security->isGranted('ROLE_REGISTRAR')) {
            return true;
        }

        // Doctors can edit their own assigned appointments
        if ($appointmentId && $this->security->isGranted('ROLE_DOCTOR')) {
            return $this->isUserOwnerOfAppointment($user, $appointmentId);
        }

        return false;
    }

    private function canCancel(User $user, ?int $appointmentId) : bool
    {
        // Registrars can cancel any appointment
        if ($this->security->isGranted('ROLE_REGISTRAR')) {
            return true;
        }

        // Doctors can cancel their own assigned appointments
        if ($appointmentId && $this->security->isGranted('ROLE_DOCTOR')) {
            return $this->isUserOwnerOfAppointment($user, $appointmentId);
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

    private function extractAppointmentId(mixed $subject) : ?int
    {
        if (is_int($subject) || is_string($subject)) {
            return (int) $subject;
        }

        if (is_array($subject) && isset($subject['id'])) {
            return (int) $subject['id'];
        }

        if (is_object($subject) && method_exists($subject, 'getId')) {
            return (int) $subject->getId();
        }

        return null;
    }
}

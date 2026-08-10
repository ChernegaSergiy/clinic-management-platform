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

use App\Bundles\AppointmentBundle\Repository\AppointmentRepository;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class AppointmentVoter extends Voter
{
    public const VIEW = 'APPOINTMENT_VIEW';
    public const VIEW_ALL = 'APPOINTMENT_VIEW_ALL';
    public const VIEW_OWN = 'APPOINTMENT_VIEW_OWN';
    public const CREATE = 'APPOINTMENT_CREATE';
    public const EDIT = 'APPOINTMENT_EDIT';
    public const EDIT_ALL = 'APPOINTMENT_EDIT_ALL';
    public const CANCEL = 'APPOINTMENT_CANCEL';

    private AppointmentRepository $appointmentRepository;
    private Security $security;

    public function __construct(
        AppointmentRepository $appointmentRepository,
        Security $security
    ) {
        $this->appointmentRepository = $appointmentRepository;
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, [
            self::VIEW,
            self::VIEW_ALL,
            self::VIEW_OWN,
            self::CREATE,
            self::EDIT,
            self::EDIT_ALL,
            self::CANCEL,
        ], true);
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

        return match ($attribute) {
            self::VIEW => $this->canView($user, $appointmentId),
            self::VIEW_ALL => $this->canViewAll(),
            self::VIEW_OWN => $this->canViewOwn(),
            self::CREATE => $this->canCreate(),
            self::EDIT => $this->canEdit($user, $appointmentId),
            self::EDIT_ALL => $this->canEditAll(),
            self::CANCEL => $this->canCancel($user, $appointmentId),
            default => false,
        };
    }

    private function canViewAll() : bool
    {
        return $this->security->isGranted('ROLE_REGISTRAR');
    }

    private function canViewOwn() : bool
    {
        return $this->security->isGranted('ROLE_DOCTOR') || $this->security->isGranted('ROLE_NURSE');
    }

    private function canCreate() : bool
    {
        return $this->security->isGranted('ROLE_REGISTRAR');
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

    private function canEditAll() : bool
    {
        return $this->security->isGranted('ROLE_REGISTRAR');
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

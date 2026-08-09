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

namespace App\Bundles\ScheduleBundle;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ScheduleVoter extends Voter
{
    public const VIEW = 'SCHEDULE_VIEW';
    public const UPDATE = 'SCHEDULE_UPDATE';
    public const MANAGE_OWN = 'SCHEDULE_MANAGE_OWN';
    public const MANAGE_ALL = 'SCHEDULE_MANAGE_ALL';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, [self::VIEW, self::UPDATE, self::MANAGE_OWN, self::MANAGE_ALL], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Administrators and Medical Managers can view and update all schedules
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MEDICAL_MANAGER')) {
            return true;
        }

        $doctorId = $this->extractDoctorId($subject);

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($user, $doctorId);
            case self::UPDATE:
                return $this->canUpdate($user, $doctorId);
            case self::MANAGE_OWN:
                return $this->canManageOwn($user, $doctorId);
            case self::MANAGE_ALL:
                return $this->canManageAll($user, $doctorId);
        }

        return false;
    }

    private function canView(User $user, ?int $doctorId) : bool
    {
        // Registrars can view all schedules to make appointments
        if ($this->security->isGranted('ROLE_REGISTRAR')) {
            return true;
        }

        // Doctors can view their own schedule
        if ($this->security->isGranted('ROLE_DOCTOR')) {
            if (!$doctorId) {
                return true; // No specific doctor provided, assume trying to view their own
            }
            return $user->getId() === $doctorId;
        }

        return false;
    }

    private function canUpdate(User $user, ?int $doctorId) : bool
    {
        // Registrars can also update schedules (e.g. adding time slots)
        if ($this->security->isGranted('ROLE_REGISTRAR')) {
            return true;
        }

        // Doctors can update their own schedule
        if ($this->security->isGranted('ROLE_DOCTOR')) {
            if (!$doctorId) {
                return true;
            }
            return $user->getId() === $doctorId;
        }

        return false;
    }

    private function canManageOwn(User $user, ?int $doctorId) : bool
    {
        // Doctors can manage their own schedule
        if ($this->security->isGranted('ROLE_DOCTOR')) {
            if (!$doctorId) {
                return true;
            }
            return $user->getId() === $doctorId;
        }

        return false;
    }

    private function canManageAll(User $user, ?int $doctorId) : bool
    {
        // Administrators and Medical Managers can manage all schedules
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MEDICAL_MANAGER')) {
            return true;
        }

        // Registrars can also manage all schedules
        if ($this->security->isGranted('ROLE_REGISTRAR')) {
            return true;
        }

        return false;
    }

    private function extractDoctorId(mixed $subject) : ?int
    {
        if (is_int($subject) || is_string($subject)) {
            return (int) $subject;
        }

        if (is_array($subject) && isset($subject['doctor_id'])) {
            return (int) $subject['doctor_id'];
        }

        if (is_object($subject) && method_exists($subject, 'getDoctorId')) {
            return (int) $subject->getDoctorId();
        }

        return null;
    }
}

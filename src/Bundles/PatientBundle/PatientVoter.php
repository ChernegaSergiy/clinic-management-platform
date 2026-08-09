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

namespace App\Bundles\PatientBundle;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepositoryInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PatientVoter extends Voter
{
    public const VIEW = 'PATIENT_VIEW';
    public const EDIT = 'PATIENT_EDIT';

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
        return in_array($attribute, [self::VIEW, self::EDIT]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Administrators can do anything with patients
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MEDICAL_MANAGER')) {
            return true;
        }

        // Determine patient ID from subject
        $patientId = $this->extractPatientId($subject);

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($user, $patientId);
            case self::EDIT:
                return $this->canEdit($user, $patientId);
        }

        return false;
    }

    private function canView(User $user, ?int $patientId) : bool
    {
        // Doctors, registrars, and nurses can view ALL patients
        if ($this->security->isGranted('ROLE_DOCTOR') ||
            $this->security->isGranted('ROLE_REGISTRAR') ||
            $this->security->isGranted('ROLE_NURSE')) {
            return true;
        }

        return false;
    }

    private function canEdit(User $user, ?int $patientId) : bool
    {
        // Registrars can edit any patient
        if ($this->security->isGranted('ROLE_REGISTRAR')) {
            return true;
        }

        // Doctors can only edit if they are assigned to this specific patient
        if ($patientId && $this->security->isGranted('ROLE_DOCTOR')) {
            return $this->appointmentRepository->isPatientAssignedToDoctor($patientId, $user->getId());
        }

        return false;
    }

    private function extractPatientId(mixed $subject) : ?int
    {
        if (is_int($subject) || is_string($subject)) {
            return (int) $subject;
        }

        if (is_array($subject) && isset($subject['id'])) {
            return (int) $subject['id'];
        }

        // If subject is a Patient entity (assuming one exists)
        if (is_object($subject) && method_exists($subject, 'getId')) {
            return (int) $subject->getId();
        }

        return null;
    }
}

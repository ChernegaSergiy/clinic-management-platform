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

namespace App\Domain\Prescription;

use App\Domain\Appointment\AppointmentRepository;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PrescriptionVoter extends Voter
{
    public const VIEW = 'PRESCRIPTION_VIEW';
    public const VIEW_ALL = 'PRESCRIPTION_VIEW_ALL';
    public const VIEW_OWN = 'PRESCRIPTION_VIEW_OWN';
    public const CREATE = 'PRESCRIPTION_CREATE';
    public const EDIT = 'PRESCRIPTION_EDIT';

    private PrescriptionRepository $prescriptionRepository;
    private AppointmentRepository $appointmentRepository;
    private Security $security;

    public function __construct(
        PrescriptionRepository $prescriptionRepository,
        AppointmentRepository $appointmentRepository,
        Security $security
    ) {
        $this->prescriptionRepository = $prescriptionRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, [self::VIEW, self::VIEW_ALL, self::VIEW_OWN, self::CREATE, self::EDIT], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Administrators and Medical Managers have full access to view and manage prescriptions
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MEDICAL_MANAGER')) {
            return true;
        }

        $prescriptionId = $this->extractPrescriptionId($subject);

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($user, $prescriptionId);
            case self::VIEW_ALL:
                return $this->canViewAll();
            case self::VIEW_OWN:
                return $this->canViewOwn();
            case self::CREATE:
                return $this->canCreate($user, $subject);
            case self::EDIT:
                return $this->canEdit($user, $prescriptionId);
        }

        return false;
    }

    private function canView(User $user, ?int $prescriptionId) : bool
    {
        // Registrars can view all prescriptions (e.g. for printing)
        if ($this->canViewAll()) {
            return true;
        }

        // Doctors and nurses can view prescriptions if they own them or are assigned to the patient
        if ($prescriptionId && $this->canViewOwn()) {
            return $this->isOwner($user, $prescriptionId);
        }

        return false;
    }

    private function canViewAll() : bool
    {
        return $this->security->isGranted('ROLE_REGISTRAR');
    }

    private function canViewOwn() : bool
    {
        return $this->security->isGranted('ROLE_DOCTOR') || $this->security->isGranted('ROLE_NURSE');
    }

    private function canCreate(User $user, mixed $subject) : bool
    {
        // Only doctors can create prescriptions
        if ($this->security->isGranted('ROLE_DOCTOR')) {
            $context = is_array($subject) ? $subject : [];
            $submittedDoctorId = $context['doctor_id'] ?? null;

            if (!$submittedDoctorId) {
                return true; // No explicit doctor specified, assuming creating for self
            }
            return $user->getId() === (int)$submittedDoctorId;
        }

        return false;
    }

    private function canEdit(User $user, ?int $prescriptionId) : bool
    {
        // Only doctors can edit prescriptions, and only if they own them
        if ($prescriptionId && $this->security->isGranted('ROLE_DOCTOR')) {
            return $this->isOwner($user, $prescriptionId);
        }

        return false;
    }

    private function isOwner(User $user, int $prescriptionId) : bool
    {
        $userId = $user->getId();
        if (!$userId) {
            return false;
        }

        $prescription = $this->prescriptionRepository->findById($prescriptionId);
        if ($prescription && (int)$prescription['doctor_id'] === $userId) {
            return true;
        }

        if ($prescription && isset($prescription['patient_id'])) {
            return $this->appointmentRepository->isPatientAssignedToDoctor((int)$prescription['patient_id'], $userId);
        }

        return false;
    }

    private function extractPrescriptionId(mixed $subject) : ?int
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

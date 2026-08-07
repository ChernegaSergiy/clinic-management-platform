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

namespace App\Bundles\PrescriptionBundle;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepositoryInterface;
use App\Bundles\PrescriptionBundle\Repository\PrescriptionRepository;
use App\Core\Auth\Policy;
use App\Core\Model\User;

class PrescriptionPolicy implements Policy
{
    private PrescriptionRepository $prescriptionRepository;
    private AppointmentRepositoryInterface $appointmentRepository;

    public function __construct(
        PrescriptionRepository $prescriptionRepository,
        AppointmentRepositoryInterface $appointmentRepository
    ) {
        $this->prescriptionRepository = $prescriptionRepository;
        $this->appointmentRepository = $appointmentRepository;
    }

    public function view(User $user, array $context) : bool
    {
        if ($user->hasPermission('prescription.view.any')) {
            return true;
        }

        if ($user->hasPermission('prescription.view.own')) {
            $prescriptionId = $context['id'] ?? null;
            if (!$prescriptionId) {
                return false;
            }

            return $this->isOwner($user, (int)$prescriptionId);
        }

        return false;
    }

    public function create(User $user, array $context) : bool
    {
        if ($user->hasPermission('prescription.create.any')) {
            return true;
        }

        if ($user->hasPermission('prescription.create.own')) {
            $submittedDoctorId = $context['doctor_id'] ?? null;
            if (!$submittedDoctorId) {
                // If doctor_id is not provided, assume it's for the current user,
                // or the controller should ensure it's the current user's ID.
                return true;
            }
            return $user->getId() === (int)$submittedDoctorId;
        }

        return false;
    }

    public function edit(User $user, array $context) : bool
    {
        if ($user->hasPermission('prescription.edit.any')) {
            return true;
        }

        if ($user->hasPermission('prescription.edit.own')) {
            $prescriptionId = $context['id'] ?? null;
            if (!$prescriptionId) {
                return false;
            }

            return $this->isOwner($user, (int)$prescriptionId);
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
        if (isset($prescription['patient_id'])) {
            return $this->appointmentRepository->isPatientAssignedToDoctor((int)$prescription['patient_id'], $userId);
        }

        return false;
    }
}

<?php

namespace App\Module\Prescription;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepositoryInterface;
use App\Core\Auth\Policy;
use App\Core\Model\User;
use App\Module\Prescription\Repository\PrescriptionRepositoryInterface;

class PrescriptionPolicy implements Policy
{
    private PrescriptionRepositoryInterface $prescriptionRepository;
    private AppointmentRepositoryInterface $appointmentRepository;

    public function __construct(
        PrescriptionRepositoryInterface $prescriptionRepository,
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

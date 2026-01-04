<?php

namespace App\Module\MedicalRecord;

use App\Core\Policy;
use App\Core\User;
use App\Module\Appointment\Repository\AppointmentRepository;

class MedicalRecordPolicy implements Policy
{
    private AppointmentRepository $appointmentRepository;

    public function __construct()
    {
        $this->appointmentRepository = new AppointmentRepository();
    }

    public function view(User $user, array $context): bool
    {
        if ($user->hasPermission('medical_record.view.any')) {
            return true;
        }

        if ($user->hasPermission('medical_record.view.own')) {
            $patientId = $context['patient_id'] ?? null;
            if (!$patientId) return false;

            return $this->isUserAssignedToPatient($user, (int)$patientId);
        }

        return false;
    }

    public function edit(User $user, array $context): bool
    {
        if ($user->hasPermission('medical_record.edit.own')) {
            $patientId = $context['patient_id'] ?? null;
            if (!$patientId) return false;

            return $this->isUserAssignedToPatient($user, (int)$patientId);
        }
        return false;
    }

    public function create(User $user, array $context): bool
    {
        return $user->hasPermission('medical_record.create');
    }

    private function isUserAssignedToPatient(User $user, int $patientId): bool
    {
        $userId = $user->getId();
        if (!$userId) {
            return false;
        }
        return $this->appointmentRepository->isPatientAssignedToDoctor($patientId, $userId);
    }
}
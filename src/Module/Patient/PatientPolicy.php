<?php

namespace App\Module\Patient;

use App\Core\Policy;
use App\Core\User;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\Patient\Repository\PatientRepository;

class PatientPolicy implements Policy
{
    private AppointmentRepository $appointmentRepository;
    private PatientRepository $patientRepository;

    public function __construct()
    {
        $this->appointmentRepository = new AppointmentRepository();
        $this->patientRepository = new PatientRepository();
    }

    public function view(User $user, array $context): bool
    {
        if ($user->hasPermission('patient.view.any')) {
            return true;
        }

        if ($user->hasPermission('patient.view.own')) {
            $patientId = $context['id'] ?? null;
            if (!$patientId) return false;
            
            return $this->isUserAssignedToPatient($user, (int)$patientId);
        }

        return false;
    }

    public function edit(User $user, array $context): bool
    {
        if ($user->hasPermission('patient.edit.any')) {
            return true;
        }

        if ($user->hasPermission('patient.edit.own')) {
            $patientId = $context['id'] ?? null;
            if (!$patientId) return false;

            return $this->isUserAssignedToPatient($user, (int)$patientId);
        }
        return false;
    }

    public function create(User $user, array $context): bool
    {
        return $user->hasPermission('patient.create');
    }

    private function isUserAssignedToPatient(User $user, int $patientId): bool
    {
        return $this->appointmentRepository->isPatientAssignedToDoctor($patientId, $user->getId());
    }
}

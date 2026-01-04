<?php

namespace App\Module\Patient;

use App\Core\Policy;
use App\Module\Appointment\Repository\AppointmentRepository;

class PatientPolicy extends Policy
{
    private AppointmentRepository $appointmentRepository;

    public function __construct()
    {
        $this->appointmentRepository = new AppointmentRepository();
    }

    public function view(mixed $resource): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();
        $patientId = (int)$resource;
        $userId = $this->userId();

        if (in_array($role, ['admin', 'medical_manager', 'registrar'])) {
            return true;
        }

        if (in_array($role, ['doctor', 'nurse']) && $userId) {
            return $this->appointmentRepository->isPatientAssignedToDoctor($patientId, $userId);
        }

        return false;
    }

    public function create(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['admin', 'medical_manager', 'registrar']);
    }

    public function update(mixed $resource): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();
        $patientId = (int)$resource;
        $userId = $this->userId();

        if (in_array($role, ['admin', 'medical_manager', 'registrar'])) {
            return true;
        }

        if (in_array($role, ['doctor']) && $userId) {
            return $this->appointmentRepository->isPatientAssignedToDoctor($patientId, $userId);
        }

        return false;
    }

    public function delete(mixed $resource): bool
    {
        return $this->create();
    }
}
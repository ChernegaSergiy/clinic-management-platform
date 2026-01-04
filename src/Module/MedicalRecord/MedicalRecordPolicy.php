<?php

namespace App\Module\MedicalRecord;

use App\Core\Policy;
use App\Module\Appointment\Repository\AppointmentRepository;

class MedicalRecordPolicy extends Policy
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
        $recordId = (int)$resource;
        $userId = $this->userId();

        if (in_array($role, ['admin', 'medical_manager'])) {
            return true;
        }

        if (in_array($role, ['doctor', 'nurse']) && $userId) {
            return $this->appointmentRepository->isPatientAssignedToDoctor($recordId, $userId);
        }

        return false;
    }

    public function create(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['admin', 'medical_manager', 'doctor']);
    }

    public function update(mixed $resource): bool
    {
        return $this->view($resource);
    }

    public function delete(mixed $resource): bool
    {
        return $this->view($resource);
    }
}

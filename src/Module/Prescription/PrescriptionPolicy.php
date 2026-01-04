<?php

namespace App\Module\Prescription;

use App\Core\Policy;
use App\Module\Prescription\Repository\PrescriptionRepository;
use App\Module\Appointment\Repository\AppointmentRepository;

class PrescriptionPolicy extends Policy
{
    private PrescriptionRepository $prescriptionRepository;
    private AppointmentRepository $appointmentRepository;

    public function __construct()
    {
        $this->prescriptionRepository = new PrescriptionRepository();
        $this->appointmentRepository = new AppointmentRepository();
    }

    public function view(mixed $resource): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();
        $prescriptionId = (int)$resource;
        $userId = $this->userId();

        if (in_array($role, ['admin', 'medical_manager'])) {
            return true;
        }

        if (in_array($role, ['doctor', 'nurse']) && $userId) {
            $prescription = $this->prescriptionRepository->findById($prescriptionId);
            if ($prescription && (int)$prescription['doctor_id'] === $userId) {
                return true;
            }
            if (isset($prescription['patient_id']) && $userId) {
                return $this->appointmentRepository->isPatientAssignedToDoctor((int)$prescription['patient_id'], $userId);
            }
        }

        return false;
    }

    public function create(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['admin', 'doctor']);
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
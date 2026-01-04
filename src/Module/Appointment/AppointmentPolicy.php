<?php

namespace App\Module\Appointment;

use App\Core\Policy;
use App\Module\Appointment\Repository\AppointmentRepository;

class AppointmentPolicy extends Policy
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
        $appointmentId = (int)$resource;
        $userId = $this->userId();

        if (in_array($role, ['admin', 'medical_manager', 'registrar', 'nurse'])) {
            return true;
        }

        if ($role === 'doctor' && $userId) {
            return $this->appointmentRepository->isAppointmentOwnedByDoctor($appointmentId, $userId);
        }

        return false;
    }

    public function create(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['admin', 'medical_manager', 'registrar', 'doctor']);
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

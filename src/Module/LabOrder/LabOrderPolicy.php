<?php

namespace App\Module\LabOrder;

use App\Core\Policy;
use App\Module\LabOrder\Repository\LabOrderRepository;

class LabOrderPolicy extends Policy
{
    private LabOrderRepository $labOrderRepository;

    public function __construct()
    {
        $this->labOrderRepository = new LabOrderRepository();
    }

    public function view(mixed $resource): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();
        $labOrderId = (int)$resource;
        $userId = $this->userId();

        if (in_array($role, ['admin', 'medical_manager'])) {
            return true;
        }

        if (in_array($role, ['lab_technician', 'doctor', 'nurse']) && $userId) {
            $labOrder = $this->labOrderRepository->findById($labOrderId);
            if ($labOrder && (int)$labOrder['doctor_id'] === $userId) {
                return true;
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

        return in_array($role, ['admin', 'medical_manager', 'doctor', 'lab_technician']);
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
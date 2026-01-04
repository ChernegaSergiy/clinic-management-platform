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

    private function isOwner(int $labOrderId): bool
    {
        $userId = $this->userId();
        if (!$userId) {
            return false;
        }

        $labOrder = $this->labOrderRepository->findById($labOrderId);
        return $labOrder && (int)$labOrder['doctor_id'] === $userId;
    }

    public function view(mixed $resource): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        if (in_array($role, ['medical_manager', 'lab_technician'])) {
            return true;
        }

        if (in_array($role, ['doctor', 'nurse'])) {
            return $this->isOwner((int)$resource);
        }

        return false;
    }

    public function create(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['doctor', 'lab_technician']);
    }

    public function update(mixed $resource): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        if ($role === 'lab_technician') {
            return true;
        }

        if ($role === 'doctor') {
            return $this->isOwner((int)$resource);
        }

        return false;
    }

    public function delete(mixed $resource): bool
    {
        return $this->isAdmin();
    }
}
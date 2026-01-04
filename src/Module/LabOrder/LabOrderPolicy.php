<?php

namespace App\Module\LabOrder;

use App\Core\Policy;
use App\Core\User;
use App\Module\LabOrder\Repository\LabOrderRepository;

class LabOrderPolicy implements Policy
{
    private LabOrderRepository $labOrderRepository;

    public function __construct()
    {
        $this->labOrderRepository = new LabOrderRepository();
    }

    public function view(User $user, array $context): bool
    {
        if ($user->hasPermission('lab_order.view.any')) {
            return true;
        }

        if ($user->hasPermission('lab_order.view.own')) {
            $labOrderId = $context['id'] ?? null;
            if (!$labOrderId) return false;

            return $this->isOwner($user, (int)$labOrderId);
        }

        return false;
    }

    public function create(User $user, array $context): bool
    {
        return $user->hasPermission('lab_order.create');
    }

    public function edit(User $user, array $context): bool
    {
        if ($user->hasPermission('lab_order.edit.any')) {
            return true;
        }

        if ($user->hasPermission('lab_order.edit.own')) {
            $labOrderId = $context['id'] ?? null;
            if (!$labOrderId) return false;

            return $this->isOwner($user, (int)$labOrderId);
        }

        return false;
    }

    private function isOwner(User $user, int $labOrderId): bool
    {
        $userId = $user->getId();
        if (!$userId) {
            return false;
        }

        $labOrder = $this->labOrderRepository->findById($labOrderId);
        return $labOrder && (int)$labOrder['doctor_id'] === $userId;
    }
}

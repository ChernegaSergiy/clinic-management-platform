<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Bundles\LabOrderBundle;

use App\Bundles\LabOrderBundle\Repository\LabOrderRepositoryInterface;
use App\Core\Auth\Policy;
use App\Core\Model\User;

class LabOrderPolicy implements Policy
{
    private LabOrderRepositoryInterface $labOrderRepository;

    public function __construct(LabOrderRepositoryInterface $labOrderRepository)
    {
        $this->labOrderRepository = $labOrderRepository;
    }

    public function view(User $user, array $context) : bool
    {
        if ($user->hasPermission('lab_order.view.any')) {
            return true;
        }

        if ($user->hasPermission('lab_order.view.own')) {
            $labOrderId = $context['id'] ?? null;
            if (!$labOrderId) {
                return false;
            }

            return $this->isOwner($user, (int)$labOrderId);
        }

        return false;
    }

    public function create(User $user, array $context) : bool
    {
        return $user->hasPermission('lab_order.create');
    }

    public function edit(User $user, array $context) : bool
    {
        if ($user->hasPermission('lab_order.edit.any')) {
            return true;
        }

        if ($user->hasPermission('lab_order.edit.own')) {
            $labOrderId = $context['id'] ?? null;
            if (!$labOrderId) {
                return false;
            }

            return $this->isOwner($user, (int)$labOrderId);
        }

        return false;
    }

    private function isOwner(User $user, int $labOrderId) : bool
    {
        $userId = $user->getId();
        if (!$userId) {
            return false;
        }

        $labOrder = $this->labOrderRepository->findById($labOrderId);
        return $labOrder && (int)$labOrder['doctor_id'] === $userId;
    }
}

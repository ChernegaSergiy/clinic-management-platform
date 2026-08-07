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

namespace App\Bundles\AppointmentBundle;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepositoryInterface;
use App\Core\Auth\Policy;
use App\Core\Model\User;

class AppointmentPolicy implements Policy
{
    private AppointmentRepositoryInterface $appointmentRepository;

    public function __construct(AppointmentRepositoryInterface $appointmentRepository)
    {
        $this->appointmentRepository = $appointmentRepository;
    }

    public function view(User $user, array $context) : bool
    {
        if ($user->hasPermission('appointment.view.any')) {
            return true;
        }

        if ($user->hasPermission('appointment.view.own')) {
            $appointmentId = $context['id'] ?? null;
            if (!$appointmentId) {
                return false;
            }

            return $this->isUserOwnerOfAppointment($user, (int)$appointmentId);
        }

        return false;
    }

    public function edit(User $user, array $context) : bool
    {
        if ($user->hasPermission('appointment.edit.any')) {
            return true;
        }

        if ($user->hasPermission('appointment.edit.own')) {
            $appointmentId = $context['id'] ?? null;
            if (!$appointmentId) {
                return false;
            }

            return $this->isUserOwnerOfAppointment($user, (int)$appointmentId);
        }
        return false;
    }

    public function create(User $user, array $context) : bool
    {
        return $user->hasPermission('appointment.create');
    }

    public function cancel(User $user, array $context) : bool
    {
        return $this->edit($user, $context);
    }

    private function isUserOwnerOfAppointment(User $user, int $appointmentId) : bool
    {
        $userId = $user->getId();
        if (!$userId) {
            return false;
        }
        return $this->appointmentRepository->isAppointmentOwnedByDoctor($appointmentId, $userId);
    }
}

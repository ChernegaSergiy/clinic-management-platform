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

namespace App\Bundles\ScheduleBundle;

use App\Bundles\ScheduleBundle\Repository\DoctorScheduleRepository;
use App\Core\Auth\Policy;
use App\Core\Model\User;

class SchedulePolicy implements Policy
{
    /** @phpstan-ignore property.onlyWritten */
    private DoctorScheduleRepository $doctorScheduleRepository;

    public function __construct(DoctorScheduleRepository $doctorScheduleRepository)
    {
        $this->doctorScheduleRepository = $doctorScheduleRepository;
    }

    public function view(User $user, array $context) : bool
    {
        if ($user->hasPermission('schedules.manage_all')) {
            return true;
        }
        if ($user->hasPermission('schedules.manage_own')) {
            $doctorId = $context['doctor_id'] ?? null;
            if (!$doctorId) {
                // If no doctor id is specified, we assume the user wants to see their own schedule.
                return true;
            }
            return $user->getId() === (int)$doctorId;
        }
        return false;
    }

    public function update(User $user, array $context) : bool
    {
        if ($user->hasPermission('schedules.manage_all')) {
            return true;
        }
        if ($user->hasPermission('schedules.manage_own')) {
            $doctorId = $context['doctor_id'] ?? null;
            if (!$doctorId) {
                return true;
            }
            return $user->getId() === (int)$doctorId;
        }
        return false;
    }
}

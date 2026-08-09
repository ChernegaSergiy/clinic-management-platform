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
use App\Core\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ScheduleVoter extends Voter
{
    /** @phpstan-ignore property.onlyWritten */
    private DoctorScheduleRepository $doctorScheduleRepository;

    public function __construct(DoctorScheduleRepository $doctorScheduleRepository)
    {
        $this->doctorScheduleRepository = $doctorScheduleRepository;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, ['ROLE_SCHEDULE_VIEW_OWN', 'ROLE_SCHEDULE_UPDATE_OWN']);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $context = is_array($subject) ? $subject : [];

        switch ($attribute) {
            case 'ROLE_SCHEDULE_VIEW_OWN':
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

            case 'ROLE_SCHEDULE_UPDATE_OWN':
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

        return false;
    }
}

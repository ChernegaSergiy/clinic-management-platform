<?php

namespace App\Module\Schedule;

use App\Core\Policy;
use App\Core\User;
use App\Module\Schedule\Repository\ScheduleRepository;

class SchedulePolicy implements Policy
{
    private ScheduleRepository $scheduleRepository;

    public function __construct()
    {
        $this->scheduleRepository = new ScheduleRepository();
    }

    public function view(User $user, array $context): bool
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

    public function update(User $user, array $context): bool
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
<?php

namespace App\Module\Schedule;

use App\Core\Auth\Policy;
use App\Core\Model\User;
use App\Module\Schedule\Repository\DoctorScheduleRepository;

class SchedulePolicy implements Policy
{
    private DoctorScheduleRepository $doctorScheduleRepository;

    public function __construct()
    {
        $this->doctorScheduleRepository = new DoctorScheduleRepository();
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

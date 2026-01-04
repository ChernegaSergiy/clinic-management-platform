<?php

namespace App\Module\Schedule;

use App\Core\Policy;
use App\Module\Schedule\Repository\ScheduleRepository;

class SchedulePolicy extends Policy
{
    private ScheduleRepository $scheduleRepository;

    public function __construct()
    {
        $this->scheduleRepository = new ScheduleRepository();
    }

    public function view(mixed $resource = null): bool
    {
        $role = $this->userRole();
        if (in_array($role, ['admin', 'medical_manager'])) {
            return true;
        }

        if ($role === 'doctor') {
            // A doctor can view their own schedule.
            // If no resource is provided, we can assume they are trying to access their own schedule page.
            // If a resource (doctor_id) is provided, we check ownership.
            return $resource === null || $this->userId() === (int) $resource;
        }

        return false;
    }

    public function update(mixed $resource = null): bool
    {
        $role = $this->userRole();
        if (in_array($role, ['admin', 'medical_manager'])) {
            return true;
        }

        if ($role === 'doctor') {
            // A doctor can update their own schedule.
            // $resource here is the user_id associated with the schedule being updated.
            if ($resource !== null) {
                return $this->userId() === (int) $resource;
            }
            // If we are on a route like /doctor/schedule/update, resource might be null.
            // The controller should enforce that the update only targets the logged-in doctor.
            // For the Gate, we can allow it if the role is doctor, assuming the controller does its job.
            return true;
        }

        return false;
    }
}

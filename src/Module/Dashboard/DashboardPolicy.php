<?php

namespace App\Module\Dashboard;

use App\Core\Policy;

class DashboardPolicy extends Policy
{
    public function view(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['medical_manager', 'registrar', 'doctor', 'nurse', 'lab_technician', 'billing', 'inventory_manager', 'hr_manager']);
    }

    public function export(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['medical_manager', 'billing']);
    }
}

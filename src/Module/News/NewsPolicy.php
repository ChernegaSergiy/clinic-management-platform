<?php

namespace App\Module\News;

use App\Core\Policy;

class NewsPolicy extends Policy
{
    public function view(mixed $resource): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['admin', 'medical_manager', 'registrar', 'doctor', 'nurse', 'billing', 'lab_technician', 'inventory_manager', 'hr_manager']);
    }

    public function create(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return $role === 'admin';
    }

    public function update(mixed $resource): bool
    {
        return $this->create();
    }

    public function delete(mixed $resource): bool
    {
        return $this->create();
    }
}
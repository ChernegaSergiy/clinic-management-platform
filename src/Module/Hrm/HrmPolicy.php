<?php

namespace App\Module\Hrm;

use App\Core\Policy;

class HrmPolicy extends Policy
{
    public function view(mixed $resource): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $userId = $this->userId();
        $role = $this->userRole();

        if (in_array($role, ['admin', 'hr_manager', 'medical_manager'])) {
            return true;
        }

        return false;
    }

    public function create(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        if (in_array($role, ['admin', 'hr_manager'])) {
            return true;
        }

        return false;
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
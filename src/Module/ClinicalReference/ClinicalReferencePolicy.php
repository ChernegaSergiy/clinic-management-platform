<?php

namespace App\Module\ClinicalReference;

use App\Core\Policy;

class ClinicalReferencePolicy extends Policy
{
    public function view(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['medical_manager']);
    }

    public function create(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['medical_manager']);
    }

    public function update(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['medical_manager']);
    }

    public function delete(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['medical_manager']);
    }
}

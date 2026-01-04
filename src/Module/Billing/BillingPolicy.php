<?php

namespace App\Module\Billing;

use App\Core\Policy;

class BillingPolicy extends Policy
{
    public function view(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['billing']);
    }

    public function create(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['billing']);
    }

    public function update(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['billing']);
    }

    public function delete(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['billing']);
    }
}

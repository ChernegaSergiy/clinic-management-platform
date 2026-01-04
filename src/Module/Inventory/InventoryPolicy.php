<?php

namespace App\Module\Inventory;

use App\Core\Policy;

class InventoryPolicy extends Policy
{
    public function view(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['inventory_manager']);
    }

    public function create(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['inventory_manager']);
    }

    public function update(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['inventory_manager']);
    }

    public function delete(mixed $resource = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $role = $this->userRole();

        return in_array($role, ['inventory_manager']);
    }
}

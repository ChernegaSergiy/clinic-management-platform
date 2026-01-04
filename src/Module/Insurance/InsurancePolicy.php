<?php

namespace App\Module\Insurance;

use App\Core\Policy;

class InsurancePolicy extends Policy
{
    public function view(mixed $resource = null): bool
    {
        return $this->isAdmin();
    }

    public function create(mixed $resource = null): bool
    {
        return $this->isAdmin();
    }

    public function update(mixed $resource = null): bool
    {
        return $this->isAdmin();
    }

    public function delete(mixed $resource = null): bool
    {
        return $this->isAdmin();
    }
}

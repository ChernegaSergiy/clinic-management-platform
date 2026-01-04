<?php

namespace App\Module\Room;

use App\Core\Policy;

class RoomPolicy extends Policy
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

<?php

namespace App\Core;

abstract class Policy
{
    abstract public function view(mixed $resource): bool;
    abstract public function create(): bool;
    abstract public function update(mixed $resource): bool;
    abstract public function delete(mixed $resource): bool;

    protected function userId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    protected function userRole(): string
    {
        return $_SESSION['user']['role_name'] ?? '';
    }

    protected function isAdmin(): bool
    {
        return $this->userRole() === 'admin';
    }
}
<?php

namespace App\Core;

class PolicyRegistry
{
    private array $policies = [];

    public function register(string $resource, string $policyClass): void
    {
        if (!class_exists($policyClass)) {
            return;
        }

        $this->policies[$resource] = $policyClass;
    }

    public function getPolicy(string $resource): ?object
    {
        if (!isset($this->policies[$resource])) {
            return null;
        }

        $policyClass = $this->policies[$resource];
        return new $policyClass();
    }

    public function hasPolicy(string $resource): bool
    {
        return isset($this->policies[$resource]);
    }

    public function clear(): void
    {
        $this->policies = [];
    }
}
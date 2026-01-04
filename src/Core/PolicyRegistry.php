<?php

namespace App\Core;

class PolicyRegistry
{
    private array $policies = [];

    public function register(string $resourceKey, string $policyClass): void
    {
        if (!class_exists($policyClass)) {
            return;
        }

        $this->policies[$resourceKey] = $policyClass;
    }

    public function get(string $resourceKey): ?object
    {
        if (!isset($this->policies[$resourceKey])) {
            return null;
        }

        $policyClass = $this->policies[$resourceKey];
        return new $policyClass();
    }
}
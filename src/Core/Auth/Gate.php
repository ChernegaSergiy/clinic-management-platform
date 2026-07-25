<?php

namespace App\Core\Auth;

use App\Core\Exception\ExitException;
use App\Core\Model\User;

class Gate
{
    private PermissionRegistry $permissionRegistry;
    private PolicyRegistry $policyRegistry;

    public function __construct(PermissionRegistry $permissionRegistry, PolicyRegistry $policyRegistry)
    {
        $this->permissionRegistry = $permissionRegistry;
        $this->policyRegistry = $policyRegistry;
    }

    public function getUser(): ?User
    {
        if (!isset($_SESSION['user'])) {
            return null;
        }
        $permissions = $_SESSION['user']['permissions'] ?? $this->permissionRegistry->getRolePermissions($_SESSION['user']['role_name']);
        return new User($_SESSION['user'], $permissions);
    }

    public function authorize(string $ability, $context = []): void
    {
        $user = $this->getUser();

        if (!$user) {
            throw new ExitException("Доступ заборонено (не автентифіковано)", 403);
        }

        if ($this->allows($ability, $context)) {
            return;
        }

        throw new ExitException("Доступ заборонено", 403);
    }

    public function allows(string $ability, $context = []): bool
    {
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $parts = explode('.', $ability, 2);

        if (count($parts) === 2) {
            $resourceKey = $parts[0];
            $verb = $parts[1];

            if ($policy = $this->policyRegistry->get($resourceKey)) {
                if (method_exists($policy, $verb)) {
                    return $policy->$verb($user, $context);
                }
            }
        }

        if ($user->hasPermission($ability)) {
            return true;
        }

        return false;
    }
}

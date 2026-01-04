<?php

namespace App\Core\Auth;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Model\User;

class Gate
{
    private static ?PermissionRegistry $permissionRegistry = null;
    private static ?PolicyRegistry $policyRegistry = null;

    public static function getUser(): ?User
    {
        if (!isset($_SESSION['user'])) {
            return null;
        }
        $permissions = self::$permissionRegistry->getRolePermissions($_SESSION['user']['role_name']);
        return new User($_SESSION['user'], $permissions);
    }

    public static function authorize(string $ability, $context = []): void
    {
        $user = self::getUser();

        if (!$user) {
            http_response_code(403);
            echo "Доступ заборонено (не автентифіковано)";
            exit();
        }

        if (self::allows($ability, $context)) {
            return;
        }

        http_response_code(403);
        echo "Доступ заборонено";
        exit();
    }

    public static function allows(string $ability, $context = []): bool
    {
        $user = self::getUser();

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

            if ($policy = self::$policyRegistry->get($resourceKey)) {
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

    public static function setPermissionRegistry(PermissionRegistry $registry): void
    {
        self::$permissionRegistry = $registry;
    }

    public static function setPolicyRegistry(PolicyRegistry $registry): void
    {
        self::$policyRegistry = $registry;
    }
}

<?php

namespace App\Core;

class Gate
{
    private static ?PermissionRegistry $permissionRegistry = null;
    private static ?PolicyRegistry $policyRegistry = null;

    private static function mapAbilityToPolicyMethod(string $verb): string
    {
        switch ($verb) {
            case 'read':
                return 'view';
            case 'write':
            case 'manage':
            case 'update':
                 return 'update';
            case 'create':
            case 'new':
                return 'create';
            case 'delete':
                return 'delete';
            default:
                return $verb;
        }
    }

    public static function authorize(string $ability, array $context = []): void
    {
        AuthGuard::check(); // Also backfills role_name

        $user = $_SESSION['user'] ?? null;
        $role = $user['role_name'] ?? '';

        if ($role === 'admin') {
            return;
        }

        // --- Policy Check ---
        if (self::$policyRegistry) {
            $parts = explode('.', $ability, 2);
            if (count($parts) === 2) {
                $resourceName = $parts[0];
                $verb = $parts[1];

                $policyKey = rtrim($resourceName, 's');


                if ($policy = self::$policyRegistry->get($policyKey)) {
                    $method = self::mapAbilityToPolicyMethod($verb);
                    $resourceId = $context[$policyKey . '_id'] ?? $context['id'] ?? null;
                    
                    if (method_exists($policy, $method)) {
                        if ($policy->$method($resourceId)) {
                            return; // Authorized by policy
                        }
                    }
                }
            }
        }
        // --- End Policy Check ---

        // Fallback to direct permission check for simple, non-policy-based permissions
        $permissions = self::getRolePermissions($role);
        if (in_array($ability, $permissions, true)) {
            return;
        }

        // If no policy and no direct permission matched, access is denied.
        http_response_code(403);
        echo "Доступ заборонено";
        exit();
    }

    public static function allows(string $ability, array $context = []): bool
    {
        AuthGuard::check(); // Ensure user data (including role_name) is hydrated

        $user = $_SESSION['user'] ?? null;
        $role = $user['role_name'] ?? '';

        if ($role === 'admin') {
            return true;
        }

        // --- Policy Check ---
        if (self::$policyRegistry) {
            $parts = explode('.', $ability, 2);
            if (count($parts) === 2) {
                $resourceName = $parts[0];
                $verb = $parts[1];

                $policyKey = rtrim($resourceName, 's');

                if ($policy = self::$policyRegistry->get($policyKey)) {
                    $method = self::mapAbilityToPolicyMethod($verb);
                    $resourceId = $context[$policyKey . '_id'] ?? $context['id'] ?? null;

                    if (method_exists($policy, $method)) {
                        return $policy->$method($resourceId);
                    }
                }
            }
        }
        // --- End Policy Check ---

        // Fallback for simple permissions
        $permissions = self::getRolePermissions($role);
        if (in_array($ability, $permissions, true)) {
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

    private static function getRolePermissions(string $role): array
    {
        if (self::$permissionRegistry) {
            $modulePermissions = self::$permissionRegistry->getRolePermissions($role);
            if (!empty($modulePermissions)) {
                return $modulePermissions;
            }
        }

        // This part can be removed if all permissions are moved to modules.
        // For now, it serves as a fallback.
        $legacyPermissions = [
            'admin' => ['*'],
            'medical_manager' => ['dashboard.view', 'dashboard.export', 'kpi.read', 'hrm.read'],
            'registrar' => ['dashboard.view', 'billing.read'],
            'doctor' => ['dashboard.view', 'notifications.read'],
            'nurse' => ['dashboard.view', 'notifications.read'],
            'lab_technician' => ['notifications.read'],
            'billing' => ['dashboard.view', 'dashboard.export'],
            'inventory_manager' => ['dashboard.view'],
            'hr_manager' => ['dashboard.view', 'hrm.read', 'hrm.write', 'hrm.manage'],
        ];

        return $legacyPermissions[$role] ?? [];
    }
}
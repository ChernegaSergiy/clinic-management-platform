<?php

namespace App\Core;

class Gate
{
    private static ?PermissionRegistry $permissionRegistry = null;
    private static ?PolicyRegistry $policyRegistry = null;
    private static array $legacyPermissions = [
        'admin' => ['*', 'system.manage', 'schedules.manage_all', 'rooms.manage'],
        'medical_manager' => [
            'dashboard.view',
            'dashboard.export',
            'patients.read_all',
            'appointments.read_all',
            'appointments.read_analytics',
            'medical.read_all',
            'clinical.manage',
            'kpi.read',
            'lab.read_all',
            'prescriptions.read_all',
            'notifications.read',
            'hrm.read',
            'schedules.manage_all',
        ],
        'registrar' => [
            'patients.read_all',
            'patients.write',
            'patients.manage',
            'appointments.read_all',
            'appointments.write',
            'billing.read',
            'notifications.read',
            'dashboard.view',
        ],
        'doctor' => [
            'dashboard.view',
            'patients.read_assigned',
            'appointments.read_assigned',
            'appointments.write_assigned',
            'medical.read_assigned',
            'medical.write_assigned',
            'prescriptions.read_assigned',
            'prescriptions.write_assigned',
            'lab.read_assigned',
            'lab.write_assigned',
            'notifications.read',
            'schedules.manage_own',
        ],
        'nurse' => [
            'dashboard.view',
            'patients.read_assigned',
            'appointments.read_assigned',
            'medical.read_assigned',
            'prescriptions.read_assigned',
            'prescriptions.write_assigned',
            'lab.read_assigned',
            'lab.write_assigned',
            'notifications.read',
        ],
        'lab_technician' => [
            'lab.read_all',
            'lab.write_all',
            'lab.manage',
            'notifications.read',
        ],
        'billing' => [
            'billing.read',
            'billing.manage',
            'patients.read_all',
            'appointments.read_all',
            'notifications.read',
            'dashboard.view',
            'dashboard.export',
        ],
        'inventory_manager' => [
            'inventory.manage',
            'notifications.read',
            'dashboard.view',
        ],
        'hr_manager' => [
            'hrm.read',
            'hrm.write',
            'hrm.manage',
            'notifications.read',
            'dashboard.view',
        ],
    ];

    public static function setPermissionRegistry(PermissionRegistry $registry): void
    {
        self::$permissionRegistry = $registry;
    }

    public static function setPolicyRegistry(PolicyRegistry $registry): void
    {
        self::$policyRegistry = $registry;
    }

    public static function authorize(string $ability, array $context = []): void
    {
        AuthGuard::check();

        $role = $_SESSION['user']['role_name'] ?? '';

        if ($role === 'admin') {
            return;
        }

        if (!self::can($ability, $context)) {
            http_response_code(403);
            echo "Доступ заборонено";
            exit();
        }
    }

    public static function can(string $ability, array $context = []): bool
    {
        AuthGuard::check();

        $role = $_SESSION['user']['role_name'] ?? '';

        if ($role === 'admin') {
            return true;
        }

        $permissions = self::getRolePermissions($role);

        if (in_array('*', $permissions, true) || in_array($ability, $permissions, true)) {
            return true;
        }

        if (self::$policyRegistry) {
            $resource = self::extractResource($ability);
            $action = self::extractAction($ability);

            if ($resource && $action) {
                $policy = self::$policyRegistry->getPolicy($resource);
                if ($policy) {
                    $resourceId = $context[$resource . '_id'] ?? null;
                    return match ($action) {
                        'read' => $policy->view($resourceId),
                        'write' => $resourceId ? $policy->update($resourceId) : $policy->create(),
                        'manage' => $resourceId ? $policy->delete($resourceId) : $policy->create(),
                        default => false,
                    };
                }
            }
        }

        return self::checkLegacyPermissions($ability, $permissions, $context);
    }

    public static function allows(string $ability, array $context = []): bool
    {
        return self::can($ability, $context);
    }

    private static function getRolePermissions(string $role): array
    {
        if (self::$permissionRegistry) {
            $modulePermissions = self::$permissionRegistry->getRolePermissions($role);
            if (!empty($modulePermissions)) {
                return $modulePermissions;
            }
        }

        return self::$legacyPermissions[$role] ?? [];
    }

    private static function extractResource(string $ability): ?string
    {
        if (preg_match('/^([^.]+)\./', $ability, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private static function extractAction(string $ability): ?string
    {
        if (preg_match('/[^.]+\.([^.]+)$/', $ability, $matches)) {
            $action = $matches[1];
            if (str_ends_with($action, '_all')) {
                return 'read';
            }
            if (str_ends_with($action, '_assigned')) {
                return 'read';
            }
            return in_array($action, ['read', 'write', 'manage']) ? $action : null;
        }
        return null;
    }

    private static function checkLegacyPermissions(string $ability, array $permissions, array $context): bool
    {
        switch ($ability) {
            case 'patients.read':
            case 'patients.write':
                if (in_array('patients.read_all', $permissions, true) && $ability === 'patients.read') {
                    return true;
                }
                if (in_array('patients.manage', $permissions, true) && $ability === 'patients.write') {
                    return true;
                }
                break;

            case 'appointments.read':
            case 'appointments.write':
                if (($ability === 'appointments.read' && in_array('appointments.read_all', $permissions, true)) ||
                    ($ability === 'appointments.write' && in_array('appointments.write', $permissions, true))) {
                    return true;
                }
                break;

            case 'medical.read':
            case 'medical.write':
                if (in_array('medical.read_all', $permissions, true) && $ability === 'medical.read') {
                    return true;
                }
                break;

            case 'lab.read':
            case 'lab.write':
                if (in_array('lab.read_all', $permissions, true) && $ability === 'lab.read') {
                    return true;
                }
                if (in_array('lab.write_all', $permissions, true) && $ability === 'lab.write') {
                    return true;
                }
                break;
        }

        return false;
    }
}
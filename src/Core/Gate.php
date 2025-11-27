<?php

namespace App\Core;

use App\Module\Appointment\Repository\AppointmentRepository;

class Gate
{
    private const ROLE_PERMISSIONS = [
        'admin' => ['*'],
        'medical_manager' => [
            'dashboard.view',
            'dashboard.export',
            'patients.read',
            'appointments.read',
            'medical.read',
            'clinical.manage',
            'kpi.read',
            'lab.read',
            'notifications.read',
        ],
        'registrar' => [
            'patients.read',
            'patients.write',
            'appointments.read',
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
            'prescriptions.write',
            'lab.write_assigned',
            'notifications.read',
        ],
        'nurse' => [
            'dashboard.view',
            'patients.read_assigned',
            'appointments.read_assigned',
            'medical.read_assigned',
            'lab.write_assigned',
            'notifications.read',
        ],
        'lab_technician' => [
            'lab.manage',
            'notifications.read',
        ],
        'billing' => [
            'billing.read',
            'billing.manage',
            'patients.read',
            'appointments.read',
            'notifications.read',
            'dashboard.view',
            'dashboard.export',
        ],
        'inventory_manager' => [
            'inventory.manage',
            'notifications.read',
            'dashboard.view',
        ],
    ];

    private static ?AppointmentRepository $appointmentRepository = null;

    private static function appointmentRepo(): AppointmentRepository
    {
        if (!self::$appointmentRepository) {
            self::$appointmentRepository = new AppointmentRepository();
        }
        return self::$appointmentRepository;
    }

    public static function authorize(string $ability, array $context = []): void
    {
        AuthGuard::check(); // Also backfills role_name

        $role = $_SESSION['user']['role_name'] ?? '';
        if ($role === 'admin') {
            return;
        }

        $permissions = self::ROLE_PERMISSIONS[$role] ?? [];

        if (in_array('*', $permissions, true) || in_array($ability, $permissions, true)) {
            return;
        }

        $fallbacks = [
            'patients.read' => 'patients.read_assigned',
            'appointments.read' => 'appointments.read_assigned',
            'medical.read' => 'medical.read_assigned',
        ];

        if (isset($fallbacks[$ability]) && in_array($fallbacks[$ability], $permissions, true)) {
            return;
        }

        if (isset($context['patient_id'])) {
            if (in_array('patients.read_assigned', $permissions, true) && in_array($ability, ['patients.read', 'patients.write'], true)) {
                $doctorId = $_SESSION['user']['id'] ?? null;
                if ($doctorId && self::appointmentRepo()->isPatientAssignedToDoctor((int)$context['patient_id'], (int)$doctorId)) {
                    return;
                }
            }
            if (in_array('medical.read_assigned', $permissions, true) && in_array($ability, ['medical.read', 'medical.write'], true)) {
                $doctorId = $_SESSION['user']['id'] ?? null;
                if ($doctorId && self::appointmentRepo()->isPatientAssignedToDoctor((int)$context['patient_id'], (int)$doctorId)) {
                    return;
                }
            }
        }

        if (isset($context['appointment_id'])) {
            if (in_array('appointments.read_assigned', $permissions, true) && in_array($ability, ['appointments.read', 'appointments.write'], true)) {
                $doctorId = $_SESSION['user']['id'] ?? null;
                if ($doctorId && self::appointmentRepo()->isAppointmentOwnedByDoctor((int)$context['appointment_id'], (int)$doctorId)) {
                    return;
                }
            }
        }

        http_response_code(403);
        echo "Доступ заборонено";
        exit();
    }

    public static function allows(string $ability, array $context = []): bool
    {
        $role = $_SESSION['user']['role_name'] ?? '';
        if ($role === 'admin') {
            return true;
        }

        $permissions = self::ROLE_PERMISSIONS[$role] ?? [];
        if (in_array('*', $permissions, true) || in_array($ability, $permissions, true)) {
            return true;
        }

        $fallbacks = [
            'patients.read' => 'patients.read_assigned',
            'appointments.read' => 'appointments.read_assigned',
            'medical.read' => 'medical.read_assigned',
        ];
        if (isset($fallbacks[$ability]) && in_array($fallbacks[$ability], $permissions, true)) {
            return true;
        }

        return false;
    }
}

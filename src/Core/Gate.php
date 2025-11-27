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
            'patients.read_all', // Changed from patients.read
            'appointments.read_all', // Changed from appointments.read
            'medical.read_all', // Changed from medical.read
            'clinical.manage',
            'kpi.read',
            'lab.read',
            'notifications.read',
        ],
        'registrar' => [
            'patients.read_all', // Changed from patients.read
            'patients.write',
            'appointments.read_all', // Changed from appointments.read
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
            'patients.read_all', // Changed from patients.read
            'appointments.read_all', // Changed from appointments.read
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
        $userId = $_SESSION['user']['id'] ?? null;

        if ($role === 'admin') {
            return;
        }

        $permissions = self::ROLE_PERMISSIONS[$role] ?? [];

        // Explicit permission check
        if (in_array('*', $permissions, true) || in_array($ability, $permissions, true)) {
            return;
        }

        // Handle specific granular permissions like 'read_assigned'
        switch ($ability) {
            case 'patients.read':
            case 'patients.write':
                if (in_array('patients.read_all', $permissions, true) && $ability === 'patients.read') {
                    return;
                }
                if (in_array('patients.write_all', $permissions, true) && $ability === 'patients.write') { // Assuming patients.write_all might exist
                    return;
                }
                if (in_array('patients.read_assigned', $permissions, true) && isset($context['patient_id']) && $userId) {
                    if (self::appointmentRepo()->isPatientAssignedToDoctor((int)$context['patient_id'], (int)$userId)) {
                        return;
                    }
                }
                break;

            case 'appointments.read':
            case 'appointments.write':
                if (in_array('appointments.read_all', $permissions, true) && $ability === 'appointments.read') {
                    return;
                }
                if (in_array('appointments.write_all', $permissions, true) && $ability === 'appointments.write') { // Assuming appointments.write_all might exist
                    return;
                }
                if (in_array('appointments.read_assigned', $permissions, true) && isset($context['appointment_id']) && $userId) {
                    if (self::appointmentRepo()->isAppointmentOwnedByDoctor((int)$context['appointment_id'], (int)$userId)) {
                        return;
                    }
                }
                break;
            
            case 'medical.read':
            case 'medical.write':
                if (in_array('medical.read_all', $permissions, true) && $ability === 'medical.read') {
                    return;
                }
                if (in_array('medical.write_all', $permissions, true) && $ability === 'medical.write') { // Assuming medical.write_all might exist
                    return;
                }
                if (in_array('medical.read_assigned', $permissions, true) && isset($context['patient_id']) && $userId) {
                    if (self::appointmentRepo()->isPatientAssignedToDoctor((int)$context['patient_id'], (int)$userId)) {
                        return;
                    }
                }
                break;
            // Add other granular permissions as needed
        }
        
        // If none of the above conditions returned, access is denied.
        http_response_code(403);
        echo "Доступ заборонено";
        exit();
    }

    public static function allows(string $ability, array $context = []): bool
    {
        AuthGuard::check(); // Ensure user data (including role_name) is hydrated

        $role = $_SESSION['user']['role_name'] ?? '';
        $userId = $_SESSION['user']['id'] ?? null;

        if ($role === 'admin') {
            return true;
        }

        $permissions = self::ROLE_PERMISSIONS[$role] ?? [];

        // Explicit permission check
        if (in_array('*', $permissions, true) || in_array($ability, $permissions, true)) {
            return true;
        }

        // Handle specific granular permissions like 'read_assigned' for allows()
        switch ($ability) {
            case 'patients.read':
                if (in_array('patients.read_all', $permissions, true)) {
                    return true;
                }
                if (in_array('patients.read_assigned', $permissions, true) && isset($context['patient_id']) && $userId) {
                    if (self::appointmentRepo()->isPatientAssignedToDoctor((int)$context['patient_id'], (int)$userId)) {
                        return true;
                    }
                }
                break;

            case 'appointments.read':
                if (in_array('appointments.read_all', $permissions, true)) {
                    return true;
                }
                if (in_array('appointments.read_assigned', $permissions, true) && isset($context['appointment_id']) && $userId) {
                    if (self::appointmentRepo()->isAppointmentOwnedByDoctor((int)$context['appointment_id'], (int)$userId)) {
                        return true;
                    }
                }
                break;
            
            case 'medical.read':
                if (in_array('medical.read_all', $permissions, true)) {
                    return true;
                }
                if (in_array('medical.read_assigned', $permissions, true) && isset($context['patient_id']) && $userId) {
                    if (self::appointmentRepo()->isPatientAssignedToDoctor((int)$context['patient_id'], (int)$userId)) {
                        return true;
                    }
                }
                break;
        }

        return false;
    }
}

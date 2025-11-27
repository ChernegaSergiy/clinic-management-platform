<?php

namespace App\Core;

use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\LabOrder\Repository\LabOrderRepository;
use App\Module\Prescription\Repository\PrescriptionRepository;

class Gate
{
    private const ROLE_PERMISSIONS = [
        'admin' => ['*'],
        'medical_manager' => [
            'dashboard.view',
            'dashboard.export',
            'patients.read_all',
            'appointments.read_all',
            'medical.read_all',
            'clinical.manage',
            'kpi.read',
            'lab.read_all',
            'prescriptions.read_all',
            'notifications.read',
        ],
        'registrar' => [
            'patients.read_all',
            'patients.write',
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
    ];

    private static ?AppointmentRepository $appointmentRepository = null;
    private static ?LabOrderRepository $labOrderRepository = null;
    private static ?PrescriptionRepository $prescriptionRepository = null;

    private static function appointmentRepo(): AppointmentRepository
    {
        if (!self::$appointmentRepository) {
            self::$appointmentRepository = new AppointmentRepository();
        }
        return self::$appointmentRepository;
    }

    private static function labOrderRepo(): LabOrderRepository
    {
        if (!self::$labOrderRepository) {
            self::$labOrderRepository = new LabOrderRepository();
        }
        return self::$labOrderRepository;
    }

    private static function prescriptionRepo(): PrescriptionRepository
    {
        if (!self::$prescriptionRepository) {
            self::$prescriptionRepository = new PrescriptionRepository();
        }
        return self::$prescriptionRepository;
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
            
            case 'lab.read':
            case 'lab.write':
                if (in_array('lab.read_all', $permissions, true) && $ability === 'lab.read') {
                    return;
                }
                if (in_array('lab.write_all', $permissions, true) && $ability === 'lab.write') { // Assuming lab.write_all might exist
                    return;
                }
                if (in_array('lab.read_assigned', $permissions, true) && isset($context['lab_order_id']) && $userId) {
                    $labOrder = self::labOrderRepo()->findById((int)$context['lab_order_id']);
                    if ($labOrder && (int)$labOrder['doctor_id'] === (int)$userId) {
                        return;
                    }
                }
                break;
            
            case 'prescriptions.read':
            case 'prescriptions.write':
                if (in_array('prescriptions.read_all', $permissions, true) && $ability === 'prescriptions.read') {
                    return;
                }
                if (in_array('prescriptions.write_all', $permissions, true) && $ability === 'prescriptions.write') { // Assuming prescriptions.write_all might exist
                    return;
                }
                if (in_array('prescriptions.read_assigned', $permissions, true) && isset($context['prescription_id']) && $userId) {
                    $prescription = self::prescriptionRepo()->findById((int)$context['prescription_id']);
                    if ($prescription && (int)$prescription['doctor_id'] === (int)$userId) {
                        return;
                    }
                }
                // Also check by patient_id if medical records are linked
                if (in_array('prescriptions.read_assigned', $permissions, true) && isset($context['patient_id']) && $userId) {
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

            case 'lab.read':
                if (in_array('lab.read_all', $permissions, true)) {
                    return true;
                }
                if (in_array('lab.read_assigned', $permissions, true) && isset($context['lab_order_id']) && $userId) {
                    $labOrder = self::labOrderRepo()->findById((int)$context['lab_order_id']);
                    if ($labOrder && (int)$labOrder['doctor_id'] === (int)$userId) {
                        return true;
                    }
                }
                break;

            case 'prescriptions.read':
                if (in_array('prescriptions.read_all', $permissions, true)) {
                    return true;
                }
                if (in_array('prescriptions.read_assigned', $permissions, true) && isset($context['prescription_id']) && $userId) {
                    $prescription = self::prescriptionRepo()->findById((int)$context['prescription_id']);
                    if ($prescription && (int)$prescription['doctor_id'] === (int)$userId) {
                        return true;
                    }
                }
                if (in_array('prescriptions.read_assigned', $permissions, true) && isset($context['patient_id']) && $userId) {
                    if (self::appointmentRepo()->isPatientAssignedToDoctor((int)$context['patient_id'], (int)$userId)) {
                        return true;
                    }
                }
                break;
        }

        return false;
    }
}

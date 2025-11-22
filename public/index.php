<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Controller\PageController;
use App\Controller\AuthController;
use App\Controller\PatientController;

// Завантаження .env файлу
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

session_start();

$router = new Router();

// Page routes
$router->add('GET', '/', [PageController::class, 'home']);
$router->add('GET', '/about', [PageController::class, 'about']);
$router->add('GET', '/contact', [PageController::class, 'contact']);

// Auth routes
$router->add('GET', '/login', [AuthController::class, 'showLoginForm']);
$router->add('POST', '/login', [AuthController::class, 'login']);
$router->add('GET', '/logout', [AuthController::class, 'logout']);
$router->add('GET', '/dashboard', [AuthController::class, 'dashboard']);

// Patient routes
$router->add('GET', '/patients', [PatientController::class, 'index']);
$router->add('GET', '/patients/new', [PatientController::class, 'create']);
$router->add('POST', '/patients/new', [PatientController::class, 'store']);
$router->add('GET', '/patients/show', [PatientController::class, 'show']);
$router->add('GET', '/patients/edit', [PatientController::class, 'edit']);
$router->add('POST', '/patients/edit', [PatientController::class, 'update']);
$router->add('GET', '/patients/toggle-status', [PatientController::class, 'toggleStatus']);
$router->add('GET', '/patients/export-csv', [PatientController::class, 'exportCsv']);
$router->add('GET', '/patients/export-json', [PatientController::class, 'exportPatientsToJson']);
$router->add('GET', '/patients/import-json', [PatientController::class, 'importPatientsFromJson']);
$router->add('POST', '/patients/import-json', [PatientController::class, 'importPatientsFromJson']);

// Appointment routes
$router->add('GET', '/appointments', [AppointmentController::class, 'index']);
$router->add('GET', '/appointments/new', [AppointmentController::class, 'create']);
$router->add('POST', '/appointments/new', [AppointmentController::class, 'store']);
$router->add('GET', '/appointments/show', [AppointmentController::class, 'show']);
$router->add('GET', '/appointments/edit', [AppointmentController::class, 'edit']);
$router->add('POST', '/appointments/edit', [AppointmentController::class, 'update']);
$router->add('GET', '/appointments/cancel', [AppointmentController::class, 'cancel']);
$router->add('GET', '/api/appointments', [AppointmentController::class, 'json']);

// Medical Record routes
$router->add('GET', '/medical-records/new', [MedicalRecordController::class, 'create']);
$router->add('POST', '/medical-records/new', [MedicalRecordController::class, 'store']);
$router->add('GET', '/medical-records/show', [MedicalRecordController::class, 'show']);

// Lab Order routes
$router->add('GET', '/lab-orders/new', [LabOrderController::class, 'create']);
$router->add('POST', '/lab-orders/new', [LabOrderController::class, 'store']);
$router->add('GET', '/lab-orders/show', [LabOrderController::class, 'show']);
$router->add('GET', '/lab-orders/edit', [LabOrderController::class, 'edit']);
$router->add('POST', '/lab-orders/edit', [LabOrderController::class, 'update']);

// Inventory routes
$router->add('GET', '/inventory', [InventoryController::class, 'index']);
$router->add('GET', '/inventory/new', [InventoryController::class, 'create']);
$router->add('POST', '/inventory/new', [InventoryController::class, 'store']);
$router->add('GET', '/inventory/show', [InventoryController::class, 'show']);
$router->add('GET', '/inventory/edit', [InventoryController::class, 'edit']);
$router->add('POST', '/inventory/edit', [InventoryController::class, 'update']);

// Billing routes
$router->add('GET', '/billing', [BillingController::class, 'index']);
$router->add('GET', '/billing/new', [BillingController::class, 'create']);
$router->add('POST', '/billing/new', [BillingController::class, 'store']);
$router->add('GET', '/billing/show', [BillingController::class, 'show']);
$router->add('GET', '/billing/edit', [BillingController::class, 'edit']);
$router->add('POST', '/billing/edit', [BillingController::class, 'update']);
$router->add('GET', '/billing/export-pdf', [BillingController::class, 'exportInvoicesToPdf']);
$router->add('GET', '/billing/export-excel', [BillingController::class, 'exportInvoicesToExcel']);
$router->add('GET', '/billing/export-csv', [BillingController::class, 'exportInvoicesToCsv']);

// Admin routes
$router->add('GET', '/admin/users', [AdminController::class, 'users']);
$router->add('GET', '/admin/users/new', [AdminController::class, 'createUser']);
$router->add('POST', '/admin/users/new', [AdminController::class, 'storeUser']);
$router->add('GET', '/admin/users/show', [AdminController::class, 'showUser']);
$router->add('GET', '/admin/users/edit', [AdminController::class, 'editUser']);
$router->add('POST', '/admin/users/edit', [AdminController::class, 'updateUser']);
$router->add('POST', '/admin/users/delete', [AdminController::class, 'deleteUser']);

// Admin Role routes
$router->add('GET', '/admin/roles', [AdminController::class, 'listRoles']);
$router->add('GET', '/admin/roles/new', [AdminController::class, 'createRole']);
$router->add('POST', '/admin/roles/new', [AdminController::class, 'storeRole']);
$router->add('GET', '/admin/roles/edit', [AdminController::class, 'editRole']);
$router->add('POST', '/admin/roles/edit', [AdminController::class, 'updateRole']);
$router->add('POST', '/admin/roles/delete', [AdminController::class, 'deleteRole']);

// Admin Dictionary routes
$router->add('GET', '/admin/dictionaries', [AdminController::class, 'listDictionaries']);
$router->add('GET', '/admin/dictionaries/new', [AdminController::class, 'createDictionary']);
$router->add('POST', '/admin/dictionaries/new', [AdminController::class, 'storeDictionary']);
$router->add('GET', '/admin/dictionaries/show', [AdminController::class, 'showDictionary']);
$router->add('GET', '/admin/dictionaries/edit', [AdminController::class, 'editDictionary']);
$router->add('POST', '/admin/dictionaries/edit', [AdminController::class, 'updateDictionary']);
$router->add('POST', '/admin/dictionaries/delete', [AdminController::class, 'deleteDictionary']);
$router->add('GET', '/admin/dictionaries/values/new', [AdminController::class, 'createDictionaryValue']);
$router->add('POST', '/admin/dictionaries/values/new', [AdminController::class, 'storeDictionaryValue']);
$router->add('GET', '/admin/dictionaries/values/edit', [AdminController::class, 'editDictionaryValue']);
$router->add('POST', '/admin/dictionaries/values/edit', [AdminController::class, 'updateDictionaryValue']);
$router->add('POST', '/admin/dictionaries/values/delete', [AdminController::class, 'deleteDictionaryValue']);

// Admin Auth Config routes
$router->add('GET', '/admin/auth_configs', [AdminController::class, 'listAuthConfigs']);
$router->add('GET', '/admin/auth_configs/new', [AdminController::class, 'createAuthConfig']);
$router->add('POST', '/admin/auth_configs/new', [AdminController::class, 'storeAuthConfig']);
$router->add('GET', '/admin/auth_configs/edit', [AdminController::class, 'editAuthConfig']);
$router->add('POST', '/admin/auth_configs/edit', [AdminController::class, 'updateAuthConfig']);
$router->add('POST', '/admin/auth_configs/delete', [AdminController::class, 'deleteAuthConfig']);

// Admin Backup Policy routes
$router->add('GET', '/admin/backup_policies', [AdminController::class, 'listBackupPolicies']);
$router->add('GET', '/admin/backup_policies/new', [AdminController::class, 'createBackupPolicy']);
$router->add('POST', '/admin/backup_policies/new', [AdminController::class, 'storeBackupPolicy']);
$router->add('GET', '/admin/backup_policies/edit', [AdminController::class, 'editBackupPolicy']);
$router->add('POST', '/admin/backup_policies/edit', [AdminController::class, 'updateBackupPolicy']);
$router->add('POST', '/admin/backup_policies/delete', [AdminController::class, 'deleteBackupPolicy']);

// Admin KPI Definition routes
$router->add('GET', '/admin/kpi_definitions', [AdminController::class, 'listKpiDefinitions']);
$router->add('GET', '/admin/kpi_definitions/new', [AdminController::class, 'createKpiDefinition']);
$router->add('POST', '/admin/kpi_definitions/new', [AdminController::class, 'storeKpiDefinition']);
$router->add('GET', '/admin/kpi_definitions/edit', [AdminController::class, 'editKpiDefinition']);
$router->add('POST', '/admin/kpi_definitions/edit', [AdminController::class, 'updateKpiDefinition']);
$router->add('POST', '/admin/kpi_definitions/delete', [AdminController::class, 'deleteKpiDefinition']);

// Dashboard route
$router->add('GET', '/dashboard', [DashboardController::class, 'index']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Controller\PageController;
use App\Module\User\AuthController;
use App\Module\Patient\PatientController;
use App\Module\Appointment\AppointmentController;
use App\Module\MedicalRecord\MedicalRecordController;
use App\Module\LabOrder\LabOrderController;
use App\Module\Inventory\InventoryController;
use App\Module\Billing\BillingController;
use App\Module\Billing\ContractController;
use App\Module\Admin\AdminController;
use App\Module\Dashboard\DashboardController;
use App\Controller\InstallController;
use App\Module\Admin\KpiController;
use App\Core\View;

// Serve static assets when requests are rewritten to index.php (e.g., missing docroot)
$requestedPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$staticFile = realpath(__DIR__ . $requestedPath);
if ($staticFile && str_starts_with($staticFile, realpath(__DIR__)) && is_file($staticFile)) {
    $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
    $mimeMap = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];
    $mime = $mimeMap[$ext] ?? mime_content_type($staticFile) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    readfile($staticFile);
    exit;
}

// Завантаження .env файлу (може бути відсутній перед інсталяцією)
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

session_start();

$router = new Router();

// Page routes
$router->add('GET', '/', [PageController::class, 'home']);
$router->add('GET', '/about', [PageController::class, 'about']);
$router->add('GET', '/contact', [PageController::class, 'contact']);
$router->add('GET', '/sitemap', [PageController::class, 'sitemap']);
$router->add('GET', '/privacy', [PageController::class, 'privacy']);

// Install routes
$router->add('GET', '/install', [InstallController::class, 'form']);
$router->add('POST', '/install', [InstallController::class, 'install']);

// Auth routes
$router->add('GET', '/login', [AuthController::class, 'showLoginForm']);
$router->add('POST', '/login', [AuthController::class, 'login']);
$router->add('GET', '/logout', [AuthController::class, 'logout']);

// Patient routes
$router->add('GET', '/patients', [PatientController::class, 'index']);
$router->add('GET', '/patients/new', [PatientController::class, 'create']);
$router->add('POST', '/patients/new', [PatientController::class, 'store']);
$router->add('GET', '/patients/show', [PatientController::class, 'show']);
$router->add('GET', '/patients/edit', [PatientController::class, 'edit']);
$router->add('POST', '/patients/edit', [PatientController::class, 'update']);
$router->add('POST', '/patients/toggle-status', [PatientController::class, 'toggleStatus']);
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
$router->add('POST', '/appointments/cancel', [AppointmentController::class, 'cancel']);
$router->add('GET', '/appointments/waitlist', [AppointmentController::class, 'waitlist']);
$router->add('POST', '/appointments/waitlist/reject', [AppointmentController::class, 'rejectWaitlist']);
$router->add('GET', '/api/appointments', [AppointmentController::class, 'json']);
$router->add('GET', '/book-appointment', [AppointmentController::class, 'publicForm']);
$router->add('POST', '/book-appointment', [AppointmentController::class, 'submitPublicForm']);

// Medical Record routes
$router->add('GET', '/medical-records/new', [MedicalRecordController::class, 'create']);
$router->add('POST', '/medical-records/new', [MedicalRecordController::class, 'store']);
$router->add('GET', '/medical-records/show', [MedicalRecordController::class, 'show']);
$router->add('GET', '/medical-records/edit', [MedicalRecordController::class, 'edit']);
$router->add('POST', '/medical-records/edit', [MedicalRecordController::class, 'update']);

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

// Contract routes
$router->add('GET', '/billing/contracts', [ContractController::class, 'index']);
$router->add('GET', '/billing/contracts/new', [ContractController::class, 'create']);
$router->add('POST', '/billing/contracts/new', [ContractController::class, 'store']);
$router->add('GET', '/billing/contracts/show', [ContractController::class, 'show']);
$router->add('GET', '/billing/contracts/edit', [ContractController::class, 'edit']);
$router->add('POST', '/billing/contracts/edit', [ContractController::class, 'update']);
$router->add('POST', '/billing/contracts/delete', [ContractController::class, 'delete']);

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

// KPI routes (legacy)
$router->add('GET', '/kpi/definitions', [KpiController::class, 'listDefinitions']);
$router->add('GET', '/kpi/definitions/new', [KpiController::class, 'createDefinition']);
$router->add('POST', '/kpi/definitions/new', [KpiController::class, 'storeDefinition']);
$router->add('GET', '/kpi/results', [KpiController::class, 'listResults']);
$router->add('POST', '/kpi/calculate', [KpiController::class, 'calculateResults']);

// Якщо додаток не встановлено, перенаправляємо на /install (крім самого інсталятора)
$installed = $_ENV['APP_INSTALLED'] ?? false;
if (!$installed && parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/install') {
    header('Location: /install');
    exit;
}

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (\Throwable $e) {
    http_response_code(500);
    $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    View::render('errors/error.html.twig', [
        'message' => 'Щось пішло не так. Ми вже розбираємося.',
        'detail' => $isDebug ? $e->getMessage() : null,
    ]);
}

<?php

namespace App\Module\Billing;

use App\Database\Database;
use App\Core\Auth\AuthGuard;
use App\Core\Auth\Gate;
use App\Core\Export\CsvExporter;
use App\Core\Export\ExcelExporter;
use App\Core\Export\PdfExporter;
use App\Core\Http\View;
use App\Core\Validation\Validator;
use Symfony\Component\Routing\Attribute\Route;
use App\Module\Appointment\Repository\AppointmentRepositoryInterface;
use App\Module\Billing\Repository\InvoiceRepository;
use App\Module\Billing\Repository\ServiceBundleRepository;
use App\Module\Billing\Repository\ServiceRepository;
use App\Module\Insurance\Repository\ClaimRepository;
use App\Module\Insurance\Repository\InsuranceCompanyRepository;
use App\Module\Insurance\Repository\PatientInsurancePolicyRepository;
use App\Module\Insurance\Service\InsuranceService;
use App\Module\MedicalRecord\Repository\MedicalRecordRepositoryInterface;
use App\Module\Patient\Repository\PatientRepositoryInterface;

class BillingController
{
    private InvoiceRepository $invoiceRepository;
    private PatientRepositoryInterface $patientRepository;
    private AppointmentRepositoryInterface $appointmentRepository;
    private MedicalRecordRepositoryInterface $medicalRecordRepository;
    private ServiceRepository $serviceRepository;
    private ServiceBundleRepository $serviceBundleRepository;
    private InsuranceService $insuranceService;
    private InsuranceCompanyRepository $insuranceCompanyRepository;
    private PatientInsurancePolicyRepository $patientInsurancePolicyRepository;
    private ClaimRepository $claimRepository;
    private \App\Core\Validation\Validator $validator;

    public function __construct(
        InvoiceRepository $invoiceRepository,
        PatientRepositoryInterface $patientRepository,
        AppointmentRepositoryInterface $appointmentRepository,
        MedicalRecordRepositoryInterface $medicalRecordRepository,
        ServiceRepository $serviceRepository,
        ServiceBundleRepository $serviceBundleRepository,
        InsuranceService $insuranceService,
        InsuranceCompanyRepository $insuranceCompanyRepository,
        PatientInsurancePolicyRepository $patientInsurancePolicyRepository,
        ClaimRepository $claimRepository,
        \App\Core\Validation\Validator $validator
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->patientRepository = $patientRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->medicalRecordRepository = $medicalRecordRepository;
        $this->serviceRepository = $serviceRepository;
        $this->serviceBundleRepository = $serviceBundleRepository;
        $this->insuranceCompanyRepository = $insuranceCompanyRepository;
        $this->patientInsurancePolicyRepository = $patientInsurancePolicyRepository;
        $this->claimRepository = $claimRepository;
        $this->insuranceService = $insuranceService;
        $this->validator = $validator;
    }

    #[Route('/billing', name: 'billing_index', methods: ['GET'])]
    public function index(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.read');
        $searchTerm = $_GET['search'] ?? '';
        $invoices = $this->invoiceRepository->findAll($searchTerm);
        View::render('@modules/Billing/templates/index.html.twig', [
            'invoices' => $invoices,
            'searchTerm' => $searchTerm,
        ]);
    }

    // --- Service Management ---
    public function listServices(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');
        $services = $this->serviceRepository->findAll();
        View::render('@modules/Billing/templates/services/index.html.twig', ['services' => $services]);
    }

    public function createService(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');
        $categories = $this->serviceRepository->findCategories();
        View::render('@modules/Billing/templates/services/new.html.twig', [
            'categories' => $categories,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeService(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /billing/services/new');
            exit();
        }

        $this->serviceRepository->save($_POST);
        $_SESSION['success_message'] = "Послугу успішно додано.";
        header('Location: /billing/services');
        exit();
    }

    // --- Service Bundle Management ---
    public function listServiceBundles(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');
        $bundles = $this->serviceBundleRepository->findAll();
        View::render('@modules/Billing/templates/bundles/index.html.twig', ['bundles' => $bundles]);
    }

    public function createServiceBundle(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');
        $services = $this->serviceRepository->findAll();
        View::render('@modules/Billing/templates/bundles/new.html.twig', [
            'services' => $services,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeServiceBundle(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required'],
            'price' => ['required', 'numeric', 'min:0'],
            'services' => ['array'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /billing/bundles/new');
            exit();
        }

        $this->serviceBundleRepository->save($_POST);
        $_SESSION['success_message'] = "Пакет послуг успішно додано.";
        header('Location: /billing/bundles');
        exit();
    }

    #[Route('/billing/new', name: 'billing_new', methods: ['GET'])]
    public function create(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        $patientId = $_GET['patient_id'] ?? null;

        $patients = $this->patientRepository->findAllActive();
        $appointments = $patientId
            ? $this->appointmentRepository->findByPatientId((int)$patientId)
            : $this->appointmentRepository->findAll();
        $medicalRecords = $patientId
            ? $this->medicalRecordRepository->findByPatientId((int)$patientId)
            : $this->medicalRecordRepository->findAll();

        $patientOptions = [];
        foreach ($patients as $patient) {
            $patientOptions[$patient['id']] = $patient['full_name'];
        }

        $appointmentOptions = [];
        foreach ($appointments as $appointment) {
            $appointmentOptions[$appointment['id']] = 'Запис #' . $appointment['id']
                . ' - ' . ($appointment['patient_name'] ?? 'Невідомий пацієнт') . ' (' . $appointment['start_time'] . ')';
        }

        $medicalRecordOptions = [];
        foreach ($medicalRecords as $record) {
            $medicalRecordOptions[$record['id']] = 'Запис #' . $record['id']
                . ' - ' . ($record['patient_name'] ?? 'Невідомий пацієнт') . ' (' . $record['visit_date'] . ')';
        }

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('@modules/Billing/templates/new.html.twig', [
            'patients' => $patientOptions,
            'appointments' => $appointmentOptions,
            'medical_records' => $medicalRecordOptions,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/billing/new', name: 'billing_store', methods: ['POST'])]
    public function store(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'patient_id' => ['required', 'numeric'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,paid,cancelled'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /billing/new');
            exit();
        }

        $invoiceId = $this->invoiceRepository->save($_POST);

        if ($invoiceId) {
            $patientId = (int)$_POST['patient_id'];
            $policies = $this->insuranceService->getPatientPolicies($patientId);
            $activePolicy = null;
            foreach ($policies as $policy) {
                if ($policy['is_active']) {
                    $activePolicy = $policy;
                    break;
                }
            }

            if ($activePolicy) {
                $amount = (float)$_POST['amount'];
                // Create a claim for the full amount
                $this->insuranceService->createClaim($invoiceId, $activePolicy['id'], $amount);

                // Update invoice with insurance and patient due amounts
                // For now, let's assume insurance covers 100%
                $this->invoiceRepository->updateInsuranceDue($invoiceId, $amount, 0.00);
            } else {
                // If no active policy, patient owes the full amount
                $amount = (float)$_POST['amount'];
                $this->invoiceRepository->updateInsuranceDue($invoiceId, 0.00, $amount);
            }
        }

        $_SESSION['success_message'] = "Рахунок успішно створено.";
        header('Location: /billing');
        exit();
    }

    #[Route('/billing/show', name: 'billing_show', methods: ['GET'])]
    public function show(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.read');

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            http_response_code(404);
            echo "Рахунок не знайдено";
            return;
        }

        View::render('@modules/Billing/templates/show.html.twig', [
            'invoice' => $invoice,
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['errors']);
    }

    #[Route('/billing/add-payment', name: 'billing_add_payment', methods: ['POST'])]
    public function addPayment(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($invoiceId);

        if (!$invoice) {
            http_response_code(404);
            echo "Рахунок не знайдено";
            return;
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /billing/show?id=' . $invoiceId);
            exit();
        }

        $this->invoiceRepository->addPayment(
            $invoiceId,
            (float)$_POST['amount'],
            $_POST['payment_method'],
            $_POST['transaction_id'] ?? null,
            $_POST['notes'] ?? null
        );

        $_SESSION['success_message'] = "Оплата успішно додана.";
        header('Location: /billing/show?id=' . $invoiceId);
        exit();
    }

    #[Route('/billing/edit', name: 'billing_edit', methods: ['GET'])]
    public function edit(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            http_response_code(404);
            echo "Рахунок не знайдено";
            return;
        }

        $patientId = $invoice['patient_id'];

        $patients = $this->patientRepository->findAllActive();
        $appointments = $this->appointmentRepository->findByPatientId(
            (int)$patientId
        );
        $medicalRecords = $this->medicalRecordRepository->findByPatientId(
            (int)$patientId
        );

        $patientOptions = [];
        foreach ($patients as $patient) {
            $patientOptions[$patient['id']] = $patient['full_name'];
        }

        $appointmentOptions = [];
        foreach ($appointments as $appointment) {
            $appointmentOptions[$appointment['id']] = 'Запис #' . $appointment['id'] . ' - '
                . ($appointment['patient_name'] ?? 'Невідомий пацієнт') . ' (' . $appointment['start_time'] . ')';
        }

        $medicalRecordOptions = [];
        foreach ($medicalRecords as $record) {
            $medicalRecordOptions[$record['id']] = 'Запис #' . $record['id'] . ' - '
                . ($record['patient_name'] ?? 'Невідомий пацієнт') . ' (' . $record['visit_date'] . ')';
        }

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('@modules/Billing/templates/edit.html.twig', [
            'invoice' => $invoice,
            'patients' => $patientOptions,
            'appointments' => $appointmentOptions,
            'medical_records' => $medicalRecordOptions,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/billing/edit', name: 'billing_update', methods: ['POST'])]
    public function update(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.manage');

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            http_response_code(404);
            echo "Рахунок не знайдено";
            return;
        }

        // TODO: Add validation
        $validator = $this->validator;
        $validator->validate($_POST, [
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,paid,cancelled'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /billing/edit?id=' . $id);
            exit();
        }

        $data = $_POST;
        $data['patient_id'] = $invoice['patient_id']; // Patient ID cannot be changed after creation
        $this->invoiceRepository->update($id, $data);
        $_SESSION['success_message'] = "Рахунок успішно оновлено.";
        header('Location: /billing/show?id=' . $id);
        exit();
    }

    #[Route('/billing/export-csv', name: 'billing_export_csv', methods: ['GET'])]
    public function exportInvoicesToCsv(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.read');

        // Fetch all invoices
        $invoices = $this->invoiceRepository->findAll();

        if (empty($invoices)) {
            $_SESSION['errors']['export'] = 'Немає рахунків для експорту.';
            header('Location: /billing');
            exit();
        }

        // Prepare data for CSV
        $headers = [
            'ID', 'Пацієнт', 'Сума', 'Статус', 'Дата виставлення', 'Дата оплати', 'Тип', 'Примітки'
        ];
        $exportData = [];
        foreach ($invoices as $invoice) {
            $exportData[] = [
                $invoice['id'],
                $invoice['patient_name'],
                $invoice['amount'],
                $invoice['status'],
                $invoice['issued_date'],
                $invoice['paid_date'],
                $invoice['type'],
                $invoice['notes'],
            ];
        }

        $exporter = new \App\Core\Export\CsvExporter($headers, $exportData);
        $exporter->download('invoices_export.csv');
    }

    #[Route('/billing/export-pdf', name: 'billing_export_pdf', methods: ['GET'])]
    public function exportInvoicesToPdf(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.read');

        $invoices = $this->invoiceRepository->findAll();

        if (empty($invoices)) {
            $_SESSION['errors']['export'] = 'Немає рахунків для експорту.';
            header('Location: /billing');
            exit();
        }

        $html = View::renderToString('@modules/Billing/templates/export_pdf.html.twig', ['invoices' => $invoices]);

        $pdfExporter = new PdfExporter();
        $pdfExporter->loadHtml($html);
        $pdfExporter->render();
        $pdfExporter->download('invoices_export.pdf');
    }

    #[Route('/billing/export-excel', name: 'billing_export_excel', methods: ['GET'])]
    public function exportInvoicesToExcel(): void
    {
        AuthGuard::check();
        Gate::authorize('billing.read');

        $invoices = $this->invoiceRepository->findAll();

        if (empty($invoices)) {
            $_SESSION['errors']['export'] = 'Немає рахунків для експорту.';
            header('Location: /billing');
            exit();
        }

        $headers = [
            'ID', 'Пацієнт', 'Сума', 'Статус', 'Дата виставлення'
        ];
        $data = [];
        foreach ($invoices as $invoice) {
            $data[] = [
                $invoice['id'],
                $invoice['patient_name'],
                $invoice['amount'],
                $invoice['status'],
                $invoice['issued_date']
            ];
        }

        $excelExporter = new ExcelExporter();
        $excelExporter->export($headers, $data, 'invoices_export.xlsx');
    }
}

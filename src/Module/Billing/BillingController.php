<?php

namespace App\Module\Billing;

use App\Core\View;
use App\Core\Validator;
use App\Repository\AppointmentRepository;
use App\Module\Billing\Repository\InvoiceRepository;
use App\Repository\MedicalRecordRepository;
use App\Repository\PatientRepository;
use App\Module\Billing\Repository\ServiceRepository;
use App\Module\Billing\Repository\ServiceBundleRepository;
use App\Core\CsvExporter;
use App\Core\PdfExporter;
use App\Core\ExcelExporter;
use App\Core\AuthGuard;

class BillingController
{
    private InvoiceRepository $invoiceRepository;
    private PatientRepository $patientRepository;
    private AppointmentRepository $appointmentRepository;
    private MedicalRecordRepository $medicalRecordRepository;
    private ServiceRepository $serviceRepository;
    private ServiceBundleRepository $serviceBundleRepository;

    public function __construct()
    {
        $this->invoiceRepository = new InvoiceRepository();
        $this->patientRepository = new PatientRepository();
        $this->appointmentRepository = new AppointmentRepository();
        $this->medicalRecordRepository = new MedicalRecordRepository();
        $this->serviceRepository = new ServiceRepository();
        $this->serviceBundleRepository = new ServiceBundleRepository();
    }

    public function index(): void
    {
        AuthGuard::check();
        $invoices = $this->invoiceRepository->findAll();
        View::render('billing/index.html.twig', ['invoices' => $invoices]);
    }

    // --- Service Management ---
    public function listServices(): void
    {
        AuthGuard::check();
        $services = $this->serviceRepository->findAll();
        View::render('billing/services/index.html.twig', ['services' => $services]);
    }

    public function createService(): void
    {
        AuthGuard::check();
        $categories = $this->serviceRepository->findCategories();
        View::render('billing/services/new.html.twig', [
            'categories' => $categories,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeService(): void
    {
        AuthGuard::check();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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
        $bundles = $this->serviceBundleRepository->findAll();
        View::render('billing/bundles/index.html.twig', ['bundles' => $bundles]);
    }

    public function createServiceBundle(): void
    {
        AuthGuard::check();
        $services = $this->serviceRepository->findAll();
        View::render('billing/bundles/new.html.twig', [
            'services' => $services,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeServiceBundle(): void
    {
        AuthGuard::check();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function create(): void
    {
        AuthGuard::check();

        $patientId = $_GET['patient_id'] ?? null;

        $patients = $this->patientRepository->findAllActive();
        $appointments = $patientId ? $this->appointmentRepository->findByPatientId((int)$patientId) : $this->appointmentRepository->findAll();
        $medicalRecords = $patientId ? $this->medicalRecordRepository->findByPatientId((int)$patientId) : $this->medicalRecordRepository->findAll();

        $patientOptions = [];
        foreach ($patients as $patient) {
            $patientOptions[$patient['id']] = $patient['full_name'];
        }

        $appointmentOptions = [];
        foreach ($appointments as $appointment) {
            $appointmentOptions[$appointment['id']] = 'Запис #' . $appointment['id'] . ' - ' . $appointment['patient_name'] . ' (' . $appointment['start_time'] . ')';
        }

        $medicalRecordOptions = [];
        foreach ($medicalRecords as $record) {
            $medicalRecordOptions[$record['id']] = 'Запис #' . $record['id'] . ' - ' . $record['patient_name'] . ' (' . $record['visit_date'] . ')';
        }

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('billing/new.html.twig', [
            'patients' => $patientOptions,
            'appointments' => $appointmentOptions,
            'medical_records' => $medicalRecordOptions,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    public function store(): void
    {
        AuthGuard::check();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

        $this->invoiceRepository->save($_POST);
        $_SESSION['success_message'] = "Рахунок успішно створено.";
        header('Location: /billing');
        exit();
    }

    public function show(): void
    {
        AuthGuard::check();

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            http_response_code(404);
            echo "Рахунок не знайдено";
            return;
        }

        View::render('billing/show.html.twig', [
            'invoice' => $invoice,
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['errors']);
    }

    public function addPayment(): void
    {
        AuthGuard::check();

        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($invoiceId);

        if (!$invoice) {
            http_response_code(404);
            echo "Рахунок не знайдено";
            return;
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function edit(): void
    {
        AuthGuard::check();

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            http_response_code(404);
            echo "Рахунок не знайдено";
            return;
        }

        $patientId = $invoice['patient_id'];

        $patients = $this->patientRepository->findAllActive();
        $appointments = $this->appointmentRepository->findByPatientId((int)$patientId);
        $medicalRecords = $this->medicalRecordRepository->findByPatientId((int)$patientId);

        $patientOptions = [];
        foreach ($patients as $patient) {
            $patientOptions[$patient['id']] = $patient['full_name'];
        }

        $appointmentOptions = [];
        foreach ($appointments as $appointment) {
            $appointmentOptions[$appointment['id']] = 'Запис #' . $appointment['id'] . ' - ' . $appointment['patient_name'] . ' (' . $appointment['start_time'] . ')';
        }

        $medicalRecordOptions = [];
        foreach ($medicalRecords as $record) {
            $medicalRecordOptions[$record['id']] = 'Запис #' . $record['id'] . ' - ' . $record['patient_name'] . ' (' . $record['visit_date'] . ')';
        }

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('billing/edit.html.twig', [
            'invoice' => $invoice,
            'patients' => $patientOptions,
            'appointments' => $appointmentOptions,
            'medical_records' => $medicalRecordOptions,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    public function update(): void
    {
        AuthGuard::check();

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            http_response_code(404);
            echo "Рахунок не знайдено";
            return;
        }

        // TODO: Add validation
        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'patient_id' => ['required', 'numeric'],
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

    public function exportInvoicesToCsv(): void
    {
        AuthGuard::check();

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

        $exporter = new \App\Core\CsvExporter($headers, $exportData);
        $exporter->download('invoices_export.csv');
    }

    public function exportInvoicesToPdf(): void
    {
        AuthGuard::check();

        $invoices = $this->invoiceRepository->findAll();

        if (empty($invoices)) {
            $_SESSION['errors']['export'] = 'Немає рахунків для експорту.';
            header('Location: /billing');
            exit();
        }

        $html = View::renderToString('billing/export_pdf.html.twig', ['invoices' => $invoices]);

        $pdfExporter = new PdfExporter();
        $pdfExporter->loadHtml($html);
        $pdfExporter->render();
        $pdfExporter->download('invoices_export.pdf');
    }

    public function exportInvoicesToExcel(): void
    {
        AuthGuard::check();

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

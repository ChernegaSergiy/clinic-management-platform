<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\AppointmentRepository;
use App\Repository\InvoiceRepository;
use App\Repository\MedicalRecordRepository;
use App\Repository\PatientRepository;

class BillingController
{
    private InvoiceRepository $invoiceRepository;
    private PatientRepository $patientRepository;
    private AppointmentRepository $appointmentRepository;
    private MedicalRecordRepository $medicalRecordRepository;

    public function __construct()
    {
        $this->invoiceRepository = new InvoiceRepository();
        $this->patientRepository = new PatientRepository();
        $this->appointmentRepository = new AppointmentRepository();
        $this->medicalRecordRepository = new MedicalRecordRepository();
    }

    public function index(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        $invoices = $this->invoiceRepository->findAll();
        View::render('billing/index.html.twig', ['invoices' => $invoices]);
    }

    public function create(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

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
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $validator = new Validator();
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
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            http_response_code(404);
            echo "Рахунок не знайдено";
            return;
        }

        View::render('billing/show.html.twig', ['invoice' => $invoice]);
    }

    public function edit(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

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
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            http_response_code(404);
            echo "Рахунок не знайдено";
            return;
        }

        // TODO: Add validation
        $validator = new Validator();
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
}

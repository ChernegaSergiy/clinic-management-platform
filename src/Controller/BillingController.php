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

        $patients = $this->patientRepository->findAllActive();
        $appointments = $this->appointmentRepository->findAll(); // TODO: filter by patient
        $medicalRecords = $this->medicalRecordRepository->findByPatientId(0); // TODO: filter by patient

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

        View::render('billing/new.html.twig', [
            'patients' => $patientOptions,
            'appointments' => $appointmentOptions,
            'medical_records' => $medicalRecordOptions,
        ]);
    }

    public function store(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        // TODO: Add validation
        $this->invoiceRepository->save($_POST);
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

        $patients = $this->patientRepository->findAllActive();
        $appointments = $this->appointmentRepository->findAll(); // TODO: filter by patient
        $medicalRecords = $this->medicalRecordRepository->findByPatientId(0); // TODO: filter by patient

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

        View::render('billing/edit.html.twig', [
            'invoice' => $invoice,
            'patients' => $patientOptions,
            'appointments' => $appointmentOptions,
            'medical_records' => $medicalRecordOptions,
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
        $data = $_POST;
        $data['patient_id'] = $invoice['patient_id']; // Patient ID cannot be changed after creation
        $this->invoiceRepository->update($id, $data);
        header('Location: /billing/show?id=' . $id);
        exit();
    }
}

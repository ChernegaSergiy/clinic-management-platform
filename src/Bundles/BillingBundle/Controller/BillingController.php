<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Bundles\BillingBundle\Controller;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepositoryInterface;
use App\Bundles\BillingBundle\Repository\InvoiceRepository;
use App\Bundles\BillingBundle\Repository\ServiceBundleRepository;
use App\Bundles\BillingBundle\Repository\ServiceRepository;
use App\Bundles\InsuranceBundle\Service\InsuranceService;
use App\Bundles\MedicalRecordBundle\Repository\MedicalRecordRepositoryInterface;
use App\Bundles\PatientBundle\Repository\PatientRepositoryInterface;
use App\Core\Export\ExcelExporter;
use App\Core\Export\PdfExporter;
use Symfony\Component\Routing\Attribute\Route;

class BillingController extends \App\Core\Controller\AbstractController
{
    private InvoiceRepository $invoiceRepository;
    private PatientRepositoryInterface $patientRepository;
    private AppointmentRepositoryInterface $appointmentRepository;
    private MedicalRecordRepositoryInterface $medicalRecordRepository;
    private ServiceRepository $serviceRepository;
    private ServiceBundleRepository $serviceBundleRepository;
    private InsuranceService $insuranceService;
    private \App\Core\Validation\Validator $validator;

    public function __construct(
        InvoiceRepository $invoiceRepository,
        PatientRepositoryInterface $patientRepository,
        AppointmentRepositoryInterface $appointmentRepository,
        MedicalRecordRepositoryInterface $medicalRecordRepository,
        ServiceRepository $serviceRepository,
        ServiceBundleRepository $serviceBundleRepository,
        InsuranceService $insuranceService,
        \App\Core\Validation\Validator $validator
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->patientRepository = $patientRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->medicalRecordRepository = $medicalRecordRepository;
        $this->serviceRepository = $serviceRepository;
        $this->serviceBundleRepository = $serviceBundleRepository;
        $this->insuranceService = $insuranceService;
        $this->validator = $validator;
    }

    #[Route('/billing', name: 'billing_index', methods: ['GET'])]
    public function index() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.read');
        $searchTerm = $_GET['search'] ?? '';
        $invoices = $this->invoiceRepository->findAll($searchTerm);
        return $this->render('@Billing/index.html.twig', [
            'invoices' => $invoices,
            'searchTerm' => $searchTerm,
        ]);
    }

    // --- Service Management ---
    public function listServices() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');
        $services = $this->serviceRepository->findAll();
        return $this->render('@Billing/services/index.html.twig', ['services' => $services]);
    }

    public function createService() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');
        $categories = $this->serviceRepository->findCategories();
        $response = $this->render('@Billing/services/new.html.twig', [
            'categories' => $categories,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    public function storeService() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/billing/services/new');
        }

        $this->serviceRepository->save($_POST);
        $_SESSION['success_message'] = "Послугу успішно додано.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/billing/services');
    }

    // --- Service Bundle Management ---
    public function listServiceBundles() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');
        $bundles = $this->serviceBundleRepository->findAll();
        return $this->render('@Billing/bundles/index.html.twig', ['bundles' => $bundles]);
    }

    public function createServiceBundle() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');
        $services = $this->serviceRepository->findAll();
        $response = $this->render('@Billing/bundles/new.html.twig', [
            'services' => $services,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    public function storeServiceBundle() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required'],
            'price' => ['required', 'numeric', 'min:0'],
            'services' => ['array'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/billing/bundles/new');
        }

        $this->serviceBundleRepository->save($_POST);
        $_SESSION['success_message'] = "Пакет послуг успішно додано.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/billing/bundles');
    }

    #[Route('/billing/new', name: 'billing_new', methods: ['GET'])]
    public function create() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

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

        return $this->render('@Billing/new.html.twig', [
            'patients' => $patientOptions,
            'appointments' => $appointmentOptions,
            'medical_records' => $medicalRecordOptions,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/billing/new', name: 'billing_store', methods: ['POST'])]
    public function store() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'patient_id' => ['required', 'numeric'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,paid,cancelled'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/billing/new');
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
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/billing');
    }

    #[Route('/billing/show', name: 'billing_show', methods: ['GET'])]
    public function show() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.read');

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            return new \Symfony\Component\HttpFoundation\Response("Рахунок не знайдено", 404);
        }

        $response = $this->render('@Billing/show.html.twig', [
            'invoice' => $invoice,
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['errors']);
        return $response;
    }

    #[Route('/billing/add-payment', name: 'billing_add_payment', methods: ['POST'])]
    public function addPayment() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($invoiceId);

        if (!$invoice) {
            return new \Symfony\Component\HttpFoundation\Response("Рахунок не знайдено", 404);
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/billing/show?id=' . $invoiceId);
        }

        $this->invoiceRepository->addPayment(
            $invoiceId,
            (float)$_POST['amount'],
            $_POST['payment_method'],
            $_POST['transaction_id'] ?? null,
            $_POST['notes'] ?? null
        );

        $_SESSION['success_message'] = "Оплата успішно додана.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/billing/show?id=' . $invoiceId);
    }

    #[Route('/billing/edit', name: 'billing_edit', methods: ['GET'])]
    public function edit() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            return new \Symfony\Component\HttpFoundation\Response("Рахунок не знайдено", 404);
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

        return $this->render('@Billing/edit.html.twig', [
            'invoice' => $invoice,
            'patients' => $patientOptions,
            'appointments' => $appointmentOptions,
            'medical_records' => $medicalRecordOptions,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/billing/edit', name: 'billing_update', methods: ['POST'])]
    public function update() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.manage');

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            return new \Symfony\Component\HttpFoundation\Response("Рахунок не знайдено", 404);
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
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/billing/edit?id=' . $id);
        }

        $data = $_POST;
        $data['patient_id'] = $invoice['patient_id']; // Patient ID cannot be changed after creation
        $this->invoiceRepository->update($id, $data);
        $_SESSION['success_message'] = "Рахунок успішно оновлено.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/billing/show?id=' . $id);
    }

    #[Route('/billing/export-csv', name: 'billing_export_csv', methods: ['GET'])]
    public function exportInvoicesToCsv() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.read');

        // Fetch all invoices
        $invoices = $this->invoiceRepository->findAll();

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
        $csvContent = $exporter->generate();

        $response = new \Symfony\Component\HttpFoundation\Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="invoices_export.csv"');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    #[Route('/billing/export-pdf', name: 'billing_export_pdf', methods: ['GET'])]
    public function exportInvoicesToPdf() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.read');

        $invoices = $this->invoiceRepository->findAll();

        $html = $this->view->renderToString('@Billing/export_pdf.html.twig', ['invoices' => $invoices]);

        $pdfExporter = new PdfExporter();
        $pdfExporter->loadHtml($html);
        $pdfExporter->render();
        $pdfContent = $pdfExporter->output();

        $response = new \Symfony\Component\HttpFoundation\Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="invoices_export.pdf"');

        return $response;
    }

    #[Route('/billing/export-excel', name: 'billing_export_excel', methods: ['GET'])]
    public function exportInvoicesToExcel() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('billing.read');

        $invoices = $this->invoiceRepository->findAll();

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
        $excelContent = $excelExporter->generate($headers, $data);

        $response = new \Symfony\Component\HttpFoundation\Response($excelContent);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="invoices_export.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}

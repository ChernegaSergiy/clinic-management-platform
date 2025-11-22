<?php

namespace App\Controller;

use App\Core\View;
use App\Core\Validator;
use App\Repository\MedicalRecordRepository;
use App\Repository\LabOrderRepository;
use App\Repository\UserRepository;
use App\Core\NotificationService;
use App\Core\QrCodeGenerator;
use App\Core\LabImportService;
use App\Repository\LabResourceRepository;

class LabOrderController
{
    private MedicalRecordRepository $medicalRecordRepository;
    private LabOrderRepository $labOrderRepository;
    private UserRepository $userRepository;
    private NotificationService $notificationService;
    private QrCodeGenerator $qrCodeGenerator;
    private LabResourceRepository $labResourceRepository;
    private LabImportService $labImportService;

    public function __construct()
    {
        $this->medicalRecordRepository = new MedicalRecordRepository();
        $this->labOrderRepository = new LabOrderRepository();
        $this->userRepository = new UserRepository();
        $this->notificationService = new NotificationService();
        $this->qrCodeGenerator = new QrCodeGenerator();
        $this->labResourceRepository = new LabResourceRepository();
        $this->labImportService = new LabImportService();
    }

    public function create(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $recordId = (int)($_GET['record_id'] ?? 0);
        $medicalRecord = $this->medicalRecordRepository->findById($recordId);

        if (!$medicalRecord) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('lab_orders/new.html.twig', [
            'medical_record' => $medicalRecord,
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

        $recordId = (int)($_GET['record_id'] ?? 0);
        $medicalRecord = $this->medicalRecordRepository->findById($recordId);

        if (!$medicalRecord) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'order_code' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /lab-orders/new?record_id=' . $recordId);
            exit();
        }

        $data = $_POST;
        $data['patient_id'] = $medicalRecord['patient_id'];
        $data['doctor_id'] = $medicalRecord['doctor_id'];
        $data['medical_record_id'] = $recordId;

        $this->labOrderRepository->save($data);
        
        $doctor = $this->userRepository->findById($medicalRecord['doctor_id']);
        if ($doctor) {
            $message = sprintf(
                'Нове лабораторне замовлення "%s" створено для медичного запису #%d.',
                $data['order_code'],
                $recordId
            );
            $this->notificationService->createNotification($doctor['id'], $message);
        }

        $_SESSION['success_message'] = "Лабораторне замовлення успішно створено.";
        header('Location: /medical-records/show?id=' . $recordId);
        exit();
    }

    public function show(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $order = $this->labOrderRepository->findById($id);

        if (!$order) {
            http_response_code(404);
            echo "Лабораторне замовлення не знайдено";
            return;
        }

        $qrCodeData = $_SERVER['HTTP_HOST'] . '/lab-orders/show?id=' . $id; // URL to the order details
        $qrCodeImage = $this->qrCodeGenerator->generateQrCodeAsBase64($qrCodeData);

        View::render('lab_orders/show.html.twig', [
            'order' => $order,
            'qrCodeImage' => $qrCodeImage,
        ]);
    }

    public function edit(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $order = $this->labOrderRepository->findById($id);

        if (!$order) {
            http_response_code(404);
            echo "Лабораторне замовлення не знайдено";
            return;
        }

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('lab_orders/edit.html.twig', [
            'order' => $order,
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

        $id = (int)($_POST['id'] ?? 0);
        $order = $this->labOrderRepository->findById($id);

        if (!$order) {
            http_response_code(404);
            echo "Лабораторне замовлення не знайдено";
            return;
        }

        // TODO: Add validation
        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'order_code' => ['required'],
            'status' => ['required', 'in:ordered,in_progress,completed,cancelled'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /lab-orders/edit?id=' . $id);
            exit();
        }

        $this->labOrderRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Лабораторне замовлення успішно оновлено.";
        header('Location: /lab-orders/show?id=' . $id);
        exit();
    }

    public function import(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        View::render('lab_orders/import.html.twig', [
            'errors' => $_SESSION['errors'] ?? [],
            'success_message' => $_SESSION['success_message'] ?? null,
        ]);
        unset($_SESSION['errors'], $_SESSION['success_message']);
    }

    public function processImport(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        if (empty($_FILES['hl7_dicom_file'])) {
            $_SESSION['errors']['file'] = 'Будь ласка, виберіть файл для завантаження.';
            header('Location: /lab-orders/import');
            exit();
        }

        $file = $_FILES['hl7_dicom_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['errors']['file'] = 'Помилка завантаження файлу: ' . $file['error'];
            header('Location: /lab-orders/import');
            exit();
        }

        // Save the file temporarily for processing
        $tempDir = __DIR__ . '/../../uploads/temp/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $tempFilename = uniqid('hl7_dicom_temp_', true) . '_' . basename($file['name']);
        $tempPath = $tempDir . $tempFilename;

        if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
            $_SESSION['errors']['file'] = 'Не вдалося зберегти завантажений файл для обробки.';
            header('Location: /lab-orders/import');
            exit();
        }

        try {
            $parsedData = $this->labImportService->validateStructural($tempPath, $file['type']);
            $_SESSION['hl7_dicom_parsed_data'] = $parsedData;
            $_SESSION['hl7_dicom_temp_path'] = $tempPath;
            $_SESSION['success_message'] = 'Файл успішно завантажено та пройшов структурну валідацію. Будь ласка, перегляньте дані перед імпортом.';
            header('Location: /lab-orders/import/confirm');
            exit();
        } catch (\Exception $e) {
            unlink($tempPath); // Clean up temp file
            $_SESSION['errors']['file'] = 'Помилка структурної валідації: ' . $e->getMessage();
            header('Location: /lab-orders/import');
            exit();
        }
    }

    public function confirmImport(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        if (empty($_SESSION['hl7_dicom_parsed_data'])) {
            $_SESSION['errors']['import'] = 'Немає даних для підтвердження імпорту.';
            header('Location: /lab-orders/import');
            exit();
        }

        View::render('lab_orders/confirm_import.html.twig', [
            'parsedData' => $_SESSION['hl7_dicom_parsed_data'],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['errors']);
    }

    public function finalizeImport(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        if (empty($_SESSION['hl7_dicom_parsed_data']) || empty($_SESSION['hl7_dicom_temp_path'])) {
            $_SESSION['errors']['import'] = 'Немає даних для фіналізації імпорту.';
            header('Location: /lab-orders/import');
            exit();
        }

        $parsedData = $_SESSION['hl7_dicom_parsed_data'];
        $tempPath = $_SESSION['hl7_dicom_temp_path'];

        try {
            $validatedData = $this->labImportService->validateLogical($parsedData);
            $orderId = $this->labImportService->importLabOrder($validatedData);

            // Clean up session and temp file
            unset($_SESSION['hl7_dicom_parsed_data']);
            unset($_SESSION['hl7_dicom_temp_path']);
            unlink($tempPath);
            
            $_SESSION['success_message'] = 'Лабораторне замовлення успішно імпортовано (ID: ' . $orderId . ').';
            header('Location: /lab-orders/show?id=' . $orderId); // Redirect to the new order
            exit();
        } catch (\Exception $e) {
            $_SESSION['errors']['import'] = 'Помилка логічної валідації або імпорту: ' . $e->getMessage();
            header('Location: /lab-orders/import/confirm');
            exit();
        }
    }
}

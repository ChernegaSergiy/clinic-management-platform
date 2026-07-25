<?php

namespace App\Module\LabOrder;

use App\Database\Database;
use App\Core\Auth\Gate;
use App\Core\Service\NotificationService;
use App\Core\Service\QrCodeGenerator;
use App\Core\Validation\Validator;
use App\Module\LabOrder\Repository\LabOrderRepositoryInterface;
use App\Module\LabOrder\Repository\LabResourceRepository;
use App\Module\LabOrder\Service\LabImportService;
use App\Module\MedicalRecord\Repository\MedicalRecordRepositoryInterface;
use App\Module\User\Repository\UserRepositoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class LabOrderController extends \App\Core\Controller\AbstractController
{
    private MedicalRecordRepositoryInterface $medicalRecordRepository;
    private LabOrderRepositoryInterface $labOrderRepository;
    private UserRepositoryInterface $userRepository;
    private NotificationService $notificationService;
    private QrCodeGenerator $qrCodeGenerator;
    private LabImportService $labImportService;
    private Validator $validator;

    public function __construct(
        MedicalRecordRepositoryInterface $medicalRecordRepository,
        LabOrderRepositoryInterface $labOrderRepository,
        UserRepositoryInterface $userRepository,
        NotificationService $notificationService,
        QrCodeGenerator $qrCodeGenerator,
        LabImportService $labImportService,
        Validator $validator
    ) {
        $this->medicalRecordRepository = $medicalRecordRepository;
        $this->labOrderRepository = $labOrderRepository;
        $this->userRepository = $userRepository;
        $this->notificationService = $notificationService;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->labImportService = $labImportService;
        $this->validator = $validator;
    }

    #[Route('/lab-orders/new', name: 'lab_orders_new_get', methods: ['GET'])]
    public function create(): void
    {
        $this->checkAuth();
        Gate::authorize('lab_order.create');

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

        $this->render('@modules/LabOrder/templates/new.html.twig', [
            'medical_record' => $medicalRecord,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/lab-orders/new', name: 'lab_orders_new_post', methods: ['POST'])]
    public function store(): void
    {
        $this->checkAuth();
        Gate::authorize('lab_order.create');

        $recordId = (int)($_GET['record_id'] ?? 0);
        $medicalRecord = $this->medicalRecordRepository->findById($recordId);

        if (!$medicalRecord) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        $validator = $this->validator;
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

        $labOrderId = $this->labOrderRepository->save($data);

        if ($labOrderId) {
            $qrCodeData = $_ENV['APP_BASE_URL'] . '/lab-orders/show?id=' . $labOrderId;
            $qrCodeHash = hash('sha256', $qrCodeData);
            $updateSuccess = $this->labOrderRepository->updateQrCodeHash($labOrderId, $qrCodeHash);
            if (!$updateSuccess) {
                error_log("Failed to update QR code hash for lab order ID: " . $labOrderId);
            }
        }

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

    #[Route('/lab-orders/show', name: 'lab_orders_show_get', methods: ['GET'])]
    public function show(): void
    {
        $this->checkAuth();

        $id = (int)($_GET['id'] ?? 0);
        $order = $this->labOrderRepository->findById($id);

        if (!$order) {
            http_response_code(404);
            echo "Лабораторне замовлення не знайдено";
            return;
        }

        Gate::authorize('lab_order.view', ['id' => $id]);

        $qrCodeData = $_ENV['APP_BASE_URL'] . '/lab-orders/show?id=' . $id;
        $qrCodeImage = $this->qrCodeGenerator->generateQrCodeAsBase64($qrCodeData);

        $this->render('@modules/LabOrder/templates/show.html.twig', [
            'order' => $order,
            'qrCodeImage' => $qrCodeImage,
        ]);
    }

    #[Route('/lab-orders/edit', name: 'lab_orders_edit_get', methods: ['GET'])]
    public function edit(): void
    {
        $this->checkAuth();

        $id = (int)($_GET['id'] ?? 0);
        $order = $this->labOrderRepository->findById($id);

        if (!$order) {
            http_response_code(404);
            echo "Лабораторне замовлення не знайдено";
            return;
        }

        Gate::authorize('lab_order.edit', ['id' => $id]);

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        $this->render('@modules/LabOrder/templates/edit.html.twig', [
            'order' => $order,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/lab-orders/edit', name: 'lab_orders_edit_post', methods: ['POST'])]
    public function update(): void
    {
        $this->checkAuth();

        $id = (int)($_POST['id'] ?? 0);
        $order = $this->labOrderRepository->findById($id);

        if (!$order) {
            http_response_code(404);
            echo "Лабораторне замовлення не знайдено";
            return;
        }

        Gate::authorize('lab_order.edit', ['id' => $id]);

        $validator = $this->validator;
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
        $this->checkAuth();
        Gate::authorize('lab_order.edit.any');

        $this->render('@modules/LabOrder/templates/import.html.twig', [
            'errors' => $_SESSION['errors'] ?? [],
            'success_message' => $_SESSION['success_message'] ?? null,
        ]);
        unset($_SESSION['errors'], $_SESSION['success_message']);
    }

    public function processImport(): void
    {
        $this->checkAuth();
        Gate::authorize('lab_order.edit.any');

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

        $tempDir = dirname(__DIR__, 3) . '/uploads/temp/';
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
            $_SESSION['success_message'] = 'Файл успішно завантажено та пройшов структурну валідацію. '
                                           . 'Будь ласка, перегляньте дані перед імпортом.';
            header('Location: /lab-orders/import/confirm');
            exit();
        } catch (\Exception $e) {
            unlink($tempPath);
            $_SESSION['errors']['file'] = 'Помилка структурної валідації: ' . $e->getMessage();
            header('Location: /lab-orders/import');
            exit();
        }
    }

    public function confirmImport(): void
    {
        $this->checkAuth();
        Gate::authorize('lab_order.edit.any');

        if (empty($_SESSION['hl7_dicom_parsed_data'])) {
            $_SESSION['errors']['import'] = 'Немає даних для підтвердження імпорту.';
            header('Location: /lab-orders/import');
            exit();
        }

        $this->render('@modules/LabOrder/templates/confirm_import.html.twig', [
            'parsedData' => $_SESSION['hl7_dicom_parsed_data'],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['errors']);
    }

    public function finalizeImport(): void
    {
        $this->checkAuth();
        Gate::authorize('lab_order.edit.any');

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

            unset($_SESSION['hl7_dicom_parsed_data']);
            unset($_SESSION['hl7_dicom_temp_path']);
            unlink($tempPath);

            $_SESSION['success_message'] = 'Лабораторне замовлення успішно імпортовано (ID: ' . $orderId . ').';
            header('Location: /lab-orders/show?id=' . $orderId);
            exit();
        } catch (\Exception $e) {
            $_SESSION['errors']['import'] = 'Помилка логічної валідації або імпорту: ' . $e->getMessage();
            header('Location: /lab-orders/import/confirm');
            exit();
        }
    }
}

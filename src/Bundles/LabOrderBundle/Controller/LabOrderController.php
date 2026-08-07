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

namespace App\Bundles\LabOrderBundle\Controller;

use App\Bundles\LabOrderBundle\Repository\LabOrderRepositoryInterface;
use App\Bundles\LabOrderBundle\Service\LabImportService;
use App\Bundles\MedicalRecordBundle\Repository\MedicalRecordRepositoryInterface;
use App\Bundles\UserBundle\Repository\UserRepositoryInterface;
use App\Core\Service\NotificationService;
use App\Core\Service\QrCodeGenerator;
use App\Core\Validation\Validator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
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
    public function create() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('lab_order.create');

        $recordId = (int)($_GET['record_id'] ?? 0);
        $medicalRecord = $this->medicalRecordRepository->findById($recordId);

        if (!$medicalRecord) {
            return new Response("Медичний запис не знайдено", 404);
        }

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        return $this->render('@LabOrder/new.html.twig', [
            'medical_record' => $medicalRecord,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/lab-orders/new', name: 'lab_orders_new_post', methods: ['POST'])]
    public function store() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('lab_order.create');

        $recordId = (int)($_GET['record_id'] ?? 0);
        $medicalRecord = $this->medicalRecordRepository->findById($recordId);

        if (!$medicalRecord) {
            return new Response("Медичний запис не знайдено", 404);
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'order_code' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse('/lab-orders/new?record_id=' . $recordId);
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
        return new RedirectResponse('/medical-records/show?id=' . $recordId);
    }

    #[Route('/lab-orders/show', name: 'lab_orders_show_get', methods: ['GET'])]
    public function show() : Response
    {
        $this->checkAuth();

        $id = (int)($_GET['id'] ?? 0);
        $order = $this->labOrderRepository->findById($id);

        if (!$order) {
            return new Response("Лабораторне замовлення не знайдено", 404);
        }

        $this->gate->authorize('lab_order.view', ['id' => $id]);

        $qrCodeData = $_ENV['APP_BASE_URL'] . '/lab-orders/show?id=' . $id;
        $qrCodeImage = $this->qrCodeGenerator->generateQrCodeAsBase64($qrCodeData);

        return $this->render('@LabOrder/show.html.twig', [
            'order' => $order,
            'qrCodeImage' => $qrCodeImage,
        ]);
    }

    #[Route('/lab-orders/edit', name: 'lab_orders_edit_get', methods: ['GET'])]
    public function edit() : Response
    {
        $this->checkAuth();

        $id = (int)($_GET['id'] ?? 0);
        $order = $this->labOrderRepository->findById($id);

        if (!$order) {
            return new Response("Лабораторне замовлення не знайдено", 404);
        }

        $this->gate->authorize('lab_order.edit', ['id' => $id]);

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        return $this->render('@LabOrder/edit.html.twig', [
            'order' => $order,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/lab-orders/edit', name: 'lab_orders_edit_post', methods: ['POST'])]
    public function update() : Response
    {
        $this->checkAuth();

        $id = (int)($_POST['id'] ?? 0);
        $order = $this->labOrderRepository->findById($id);

        if (!$order) {
            return new Response("Лабораторне замовлення не знайдено", 404);
        }

        $this->gate->authorize('lab_order.edit', ['id' => $id]);

        $validator = $this->validator;
        $validator->validate($_POST, [
            'order_code' => ['required'],
            'status' => ['required', 'in:ordered,in_progress,completed,cancelled'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse('/lab-orders/edit?id=' . $id);
        }

        $this->labOrderRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Лабораторне замовлення успішно оновлено.";
        return new RedirectResponse('/lab-orders/show?id=' . $id);
    }

    #[Route('/lab-orders/import', name: 'lab_orders_import', methods: ['GET'])]
    public function import() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('lab_order.edit.any');

        $response = $this->render('@LabOrder/import.html.twig', [
            'errors' => $_SESSION['errors'] ?? [],
            'success_message' => $_SESSION['success_message'] ?? null,
        ]);
        unset($_SESSION['errors'], $_SESSION['success_message']);
        return $response;
    }

    #[Route('/lab-orders/import', name: 'lab_orders_import_post', methods: ['POST'])]
    public function processImport() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('lab_order.edit.any');

        if (empty($_FILES['hl7_dicom_file'])) {
            $_SESSION['errors']['file'] = 'Будь ласка, виберіть файл для завантаження.';
            return new RedirectResponse('/lab-orders/import');
        }

        $file = $_FILES['hl7_dicom_file'];

        if (UPLOAD_ERR_OK !== $file['error']) {
            $_SESSION['errors']['file'] = 'Помилка завантаження файлу: ' . $file['error'];
            return new RedirectResponse('/lab-orders/import');
        }

        $tempDir = dirname(__DIR__, 3) . '/uploads/temp/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $tempFilename = uniqid('hl7_dicom_temp_', true) . '_' . basename($file['name']);
        $tempPath = $tempDir . $tempFilename;

        if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
            $_SESSION['errors']['file'] = 'Не вдалося зберегти завантажений файл для обробки.';
            return new RedirectResponse('/lab-orders/import');
        }

        try {
            $parsedData = $this->labImportService->validateStructural($tempPath, $file['type']);
            $_SESSION['hl7_dicom_parsed_data'] = $parsedData;
            $_SESSION['hl7_dicom_temp_path'] = $tempPath;
            $_SESSION['success_message'] = 'Файл успішно завантажено та пройшов структурну валідацію. '
                                           . 'Будь ласка, перегляньте дані перед імпортом.';
            return new RedirectResponse('/lab-orders/import/confirm');
        } catch (\Exception $e) {
            unlink($tempPath);
            $_SESSION['errors']['file'] = 'Помилка структурної валідації: ' . $e->getMessage();
            return new RedirectResponse('/lab-orders/import');
        }
    }

    #[Route('/lab-orders/import/confirm', name: 'lab_orders_import_confirm', methods: ['GET'])]
    public function confirmImport() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('lab_order.edit.any');

        if (empty($_SESSION['hl7_dicom_parsed_data'])) {
            $_SESSION['errors']['import'] = 'Немає даних для підтвердження імпорту.';
            return new RedirectResponse('/lab-orders/import');
        }

        $response = $this->render('@LabOrder/confirm_import.html.twig', [
            'parsedData' => $_SESSION['hl7_dicom_parsed_data'],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['errors']);
        return $response;
    }

    #[Route('/lab-orders/import/finalize', name: 'lab_orders_import_finalize', methods: ['POST'])]
    public function finalizeImport() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('lab_order.edit.any');

        if (empty($_SESSION['hl7_dicom_parsed_data']) || empty($_SESSION['hl7_dicom_temp_path'])) {
            $_SESSION['errors']['import'] = 'Немає даних для фіналізації імпорту.';
            return new RedirectResponse('/lab-orders/import');
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
            return new RedirectResponse('/lab-orders/show?id=' . $orderId);
        } catch (\Exception $e) {
            $_SESSION['errors']['import'] = 'Помилка логічної валідації або імпорту: ' . $e->getMessage();
            return new RedirectResponse('/lab-orders/import/confirm');
        }
    }
}

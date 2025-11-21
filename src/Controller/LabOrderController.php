<?php

namespace App\Controller;

use App\Core\View;
use App\Core\Validator;
use App\Repository\MedicalRecordRepository;
use App\Repository\LabOrderRepository;
use App\Repository\UserRepository;
use App\Core\NotificationService;
use App\Core\QrCodeGenerator; // Додано QrCodeGenerator

class LabOrderController
{
    private MedicalRecordRepository $medicalRecordRepository;
    private LabOrderRepository $labOrderRepository;
    private UserRepository $userRepository;
    private NotificationService $notificationService;
    private QrCodeGenerator $qrCodeGenerator; // Ініціалізація

    public function __construct()
    {
        $this->medicalRecordRepository = new MedicalRecordRepository();
        $this->labOrderRepository = new LabOrderRepository();
        $this->userRepository = new UserRepository();
        $this->notificationService = new NotificationService();
        $this->qrCodeGenerator = new QrCodeGenerator(); // Ініціалізація
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

        $validator = new Validator();
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

        View::render('lab_orders/show.html.twig', ['order' => $order]);
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

        $id = (int)($_GET['id'] ?? 0);
        $order = $this->labOrderRepository->findById($id);

        if (!$order) {
            http_response_code(404);
            echo "Лабораторне замовлення не знайдено";
            return;
        }

        // TODO: Add validation
        $validator = new Validator();
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
}

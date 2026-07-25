<?php

namespace App\Module\MedicalRecord;

use App\Database\Database;
use App\Core\Auth\Gate;
use App\Core\Service\AttachmentService;
use App\Core\Service\AuditLogger;
use App\Module\Appointment\Repository\AppointmentRepositoryInterface;
use App\Module\ClinicalReference\Repository\IcdCodeRepository;
use App\Module\ClinicalReference\Repository\InterventionCodeRepository;
use App\Module\LabOrder\Repository\LabOrderRepositoryInterface;
use App\Module\MedicalRecord\Repository\MedicalRecordRepositoryInterface;
use App\Core\Validation\Validator;
use Symfony\Component\Routing\Attribute\Route;

class MedicalRecordController extends \App\Core\Controller\AbstractController
{
    private MedicalRecordRepositoryInterface $medicalRecordRepository;
    private AppointmentRepositoryInterface $appointmentRepository;
    private LabOrderRepositoryInterface $labOrderRepository;
    private IcdCodeRepository $icdCodeRepository;
    private InterventionCodeRepository $interventionCodeRepository;
    private AttachmentService $attachmentService;
    private AuditLogger $auditLogger;
    private Validator $validator;

    public function __construct(
        MedicalRecordRepositoryInterface $medicalRecordRepository,
        AppointmentRepositoryInterface $appointmentRepository,
        LabOrderRepositoryInterface $labOrderRepository,
        IcdCodeRepository $icdCodeRepository,
        InterventionCodeRepository $interventionCodeRepository,
        AttachmentService $attachmentService,
        AuditLogger $auditLogger,
        Validator $validator
    ) {
        $this->medicalRecordRepository = $medicalRecordRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->labOrderRepository = $labOrderRepository;
        $this->icdCodeRepository = $icdCodeRepository;
        $this->interventionCodeRepository = $interventionCodeRepository;
        $this->attachmentService = $attachmentService;
        $this->auditLogger = $auditLogger;
        $this->validator = $validator;
    }

    #[Route('/medical-records/new', name: 'medical_records_new_get', methods: ['GET'])]
    public function create(): void
    {
        $this->checkAuth();
        Gate::authorize('medical_record.create');

        $appointmentId = (int)($_GET['appointment_id'] ?? 0);
        $appointment = $this->appointmentRepository->findById($appointmentId);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        $this->render('@modules/MedicalRecord/templates/new.html.twig', [
            'appointment' => $appointment,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/medical-records', name: 'medical_records_index', methods: ['GET'])]
    public function index(): void
    {
        $this->checkAuth();
        $user = Gate::getUser();
        $searchTerm = $_GET['search'] ?? '';
        $records = [];

        if (Gate::allows('medical_record.view.any')) {
            $records = $this->medicalRecordRepository->findAll($searchTerm);
        } elseif (Gate::allows('medical_record.view.own')) {
            if ($user && $user->getId()) {
                $records = $this->medicalRecordRepository->findByDoctorId($user->getId(), $searchTerm);
            }
        }

        $this->render('@modules/MedicalRecord/templates/index.html.twig', [
            'records' => $records,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/medical-records/new', name: 'medical_records_new_post', methods: ['POST'])]
    public function store(): void
    {
        $this->checkAuth();
        Gate::authorize('medical_record.create');

        $appointmentId = (int)($_GET['appointment_id'] ?? 0);
        $appointment = $this->appointmentRepository->findById($appointmentId);

        if (!$appointment) {
            http_response_code(404);
            echo "Запис не знайдено";
            return;
        }

        if (!empty($_POST['visit_date'])) {
            try {
                $dt = new \DateTime($_POST['visit_date']);
                $_POST['visit_date'] = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
            }
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'diagnosis_code' => ['required'],
            'visit_date' => ['required', 'datetime'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /medical-records/new?appointment_id=' . $appointmentId);
            exit();
        }

        $data = $_POST;
        $data['patient_id'] = $appointment['patient_id'];
        $data['appointment_id'] = $appointmentId;
        $data['doctor_id'] = $appointment['doctor_id'];

        $medicalRecordId = $this->medicalRecordRepository->save($data);

        if ($medicalRecordId && !empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['name'] as $key => $name) {
                if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileData = [
                        'name' => $name,
                        'type' => $_FILES['attachments']['type'][$key],
                        'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                        'error' => $_FILES['attachments']['error'][$key],
                        'size' => $_FILES['attachments']['size'][$key],
                    ];
                    $this->attachmentService->uploadAttachment(
                        $fileData,
                        'medical_record',
                        $medicalRecordId,
                        Gate::getUser()->getId()
                    );
                }
            }
        }

        $this->appointmentRepository->updateStatus($appointmentId, 'completed');

        header('Location: /patients/show?id=' . $appointment['patient_id']);
        exit();
    }

    #[Route('/medical-records/show', name: 'medical_records_show', methods: ['GET'])]
    public function show(): void
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($id);

        if (!$record) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        Gate::authorize('medical_record.view', ['id' => $id]);

        $this->auditLogger->log(
            'medical_record',
            $id,
            'view',
            null,
            null,
            Gate::getUser()->getId()
        );

        $labOrders = $this->labOrderRepository->findByMedicalRecordId($id);
        $attachments = $this->attachmentService->getAttachmentsForEntity('medical_record', $id);

        $this->render('@modules/MedicalRecord/templates/show.html.twig', [
            'record' => $record,
            'lab_orders' => $labOrders,
            'attachments' => $attachments,
        ]);
    }

    #[Route('/medical-records/icd-codes', name: 'medical_records_icd_codes', methods: ['GET'])]
    public function getIcdCodes(): void
    {
        $this->checkAuth();
        Gate::authorize('clinical.manage');

        $searchTerm = $_GET['search'] ?? '';
        $codes = $this->icdCodeRepository->searchByCodeOrDescription($searchTerm);

        header('Content-Type: application/json');
        echo json_encode($codes);
    }

    #[Route('/medical-records/intervention-codes', name: 'medical_records_intervention_codes', methods: ['GET'])]
    public function getInterventionCodes(): void
    {
        $this->checkAuth();
        Gate::authorize('clinical.manage');

        $searchTerm = $_GET['search'] ?? '';
        $codes = $this->interventionCodeRepository->searchByCodeOrDescription($searchTerm);

        header('Content-Type: application/json');
        echo json_encode($codes);
    }

    #[Route('/medical-records/edit', name: 'medical_records_edit_get', methods: ['GET'])]
    public function edit(): void
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($id);

        if (!$record) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        Gate::authorize('medical_record.edit', ['id' => $id]);

        $this->render('@modules/MedicalRecord/templates/edit.html.twig', [
            'record' => $record,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/medical-records/edit', name: 'medical_records_edit_post', methods: ['POST'])]
    public function update(): void
    {
        $this->checkAuth();
        $id = (int)($_POST['id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($id);

        if (!$record) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        Gate::authorize('medical_record.edit', ['id' => $id]);

        if (!empty($_POST['visit_date'])) {
            try {
                $dt = new \DateTime($_POST['visit_date']);
                $_POST['visit_date'] = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
            }
        }

        $validator = $this->validator;
        $validator->validate(
            $_POST,
            [
                'diagnosis_code' => ['required'],
                'visit_date' => ['required', 'datetime'],
                'icd_codes' => ['array'],
                'intervention_codes' => ['array'],
            ]
        );

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /medical-records/edit?id=' . $id);
            exit();
        }

        $data = $_POST;
        $data['patient_id'] = $record['patient_id'];
        $data['appointment_id'] = $record['appointment_id'];
        $data['doctor_id'] = $record['doctor_id'];

        $this->medicalRecordRepository->update(
            $id,
            $data
        );

        header('Location: /medical-records/show?id=' . $id);
        exit();
    }

    #[Route('/medical-records/attachments/upload', name: 'medical_records_attachments_upload', methods: ['POST'])]
    public function uploadAttachment(): void
    {
        $this->checkAuth();
        $medicalRecordId = (int)($_POST['medical_record_id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($medicalRecordId);

        if (!$record) {
            http_response_code(404);
            echo "Медичний запис не знайдено";
            return;
        }

        Gate::authorize('medical_record.edit', ['id' => $medicalRecordId]);

        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['name'] as $key => $name) {
                if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileData = [
                        'name' => $name,
                        'type' => $_FILES['attachments']['type'][$key],
                        'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                        'error' => $_FILES['attachments']['error'][$key],
                        'size' => $_FILES['attachments']['size'][$key],
                    ];
                    $this->attachmentService->uploadAttachment(
                        $fileData,
                        'medical_record',
                        $medicalRecordId,
                        Gate::getUser()->getId()
                    );
                }
            }
        }

        header('Location: /medical-records/show?id=' . $medicalRecordId);
        exit();
    }

    #[Route('/medical-records/attachments/download', name: 'medical_records_attachments_download', methods: ['GET'])]
    public function downloadAttachment(): void
    {
        $this->checkAuth();
        $attachmentId = (int)($_GET['attachment_id'] ?? 0);
        $attachment = $this->attachmentService->getAttachmentById($attachmentId);

        if (!$attachment || $attachment['entity_type'] !== 'medical_record') {
            http_response_code(404);
            echo "Вкладення не знайдено";
            return;
        }

        $medicalRecordId = (int)$attachment['entity_id'];
        $record = $this->medicalRecordRepository->findById($medicalRecordId);

        if (!$record) {
            http_response_code(404);
            echo "Медичний запис, пов'язаний із вкладенням, не знайдено";
            return;
        }

        Gate::authorize('medical_record.view', ['id' => $medicalRecordId]);

        $uploadBase = dirname(__DIR__, 3) . '/uploads/';
        $candidates = [];
        if (!empty($attachment['filepath'])) {
            $candidates[] = $uploadBase . ltrim($attachment['filepath'], '/');
        }
        $path = $uploadBase . 'medical_record/' . $medicalRecordId . '/';
        $path .= ($attachment['filename'] ?? '');
        $candidates[] = $path;

        $fullPath = null;
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $fullPath = $path;
                break;
            }
        }

        if (!$fullPath) {
            http_response_code(404);
            echo "Файл не знайдено на сервері";
            return;
        }

        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['filename'] . '"');
        header('Content-Length: ' . $attachment['size']);
        readfile($fullPath);
        exit();
    }
}

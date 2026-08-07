<?php

namespace App\Bundles\MedicalRecordBundle\Controller;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepositoryInterface;
use App\Bundles\ClinicalReferenceBundle\Repository\IcdCodeRepository;
use App\Bundles\ClinicalReferenceBundle\Repository\InterventionCodeRepository;
use App\Bundles\LabOrderBundle\Repository\LabOrderRepositoryInterface;
use App\Bundles\MedicalRecordBundle\Repository\MedicalRecordRepositoryInterface;
use App\Core\Service\AttachmentService;
use App\Core\Service\AuditLogger;
use App\Core\Validation\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
    public function create() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('medical_record.create');

        $appointmentId = (int)($_GET['appointment_id'] ?? 0);
        $appointment = $this->appointmentRepository->findById($appointmentId);

        if (!$appointment) {
            return new Response("Запис не знайдено", 404);
        }

        $response = $this->render('@MedicalRecord/create.html.twig', [
            'appointment' => $appointment,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/medical-records', name: 'medical_records_index', methods: ['GET'])]
    public function index() : Response
    {
        $this->checkAuth();
        $user = $this->gate->getUser();
        $searchTerm = $_GET['search'] ?? '';
        $records = [];

        if ($this->gate->allows('medical_record.view.any')) {
            $records = $this->medicalRecordRepository->findAll($searchTerm);
        } elseif ($this->gate->allows('medical_record.view.own')) {
            if ($user && $user->getId()) {
                $records = $this->medicalRecordRepository->findByDoctorId($user->getId(), $searchTerm);
            }
        }

        return $this->render('@MedicalRecord/index.html.twig', [
            'records' => $records,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/medical-records/new', name: 'medical_records_new_post', methods: ['POST'])]
    public function store() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('medical_record.create');

        $appointmentId = (int)($_GET['appointment_id'] ?? 0);
        $appointment = $this->appointmentRepository->findById($appointmentId);

        if (!$appointment) {
            return new Response("Запис не знайдено", 404);
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
            return new RedirectResponse('/medical-records/new?appointment_id=' . $appointmentId);
        }

        $data = $_POST;
        $data['patient_id'] = $appointment['patient_id'];
        $data['appointment_id'] = $appointmentId;
        $data['doctor_id'] = $appointment['doctor_id'];

        $medicalRecordId = $this->medicalRecordRepository->save($data);

        if ($medicalRecordId && !empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['name'] as $key => $name) {
                if (UPLOAD_ERR_OK === $_FILES['attachments']['error'][$key]) {
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
                        $this->gate->getUser()->getId()
                    );
                }
            }
        }

        $this->appointmentRepository->updateStatus($appointmentId, 'completed');

        return new RedirectResponse('/patients/show?id=' . $appointment['patient_id']);
    }

    #[Route('/medical-records/show', name: 'medical_records_show', methods: ['GET'])]
    public function show() : Response
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($id);

        if (!$record) {
            return new Response("Медичний запис не знайдено", 404);
        }

        $this->gate->authorize('medical_record.view', ['id' => $id]);

        $this->auditLogger->log(
            'medical_record',
            $id,
            'view',
            null,
            null,
            $this->gate->getUser()->getId()
        );

        $labOrders = $this->labOrderRepository->findByMedicalRecordId($id);
        $attachments = $this->attachmentService->getAttachmentsForEntity('medical_record', $id);

        return $this->render('@MedicalRecord/show.html.twig', [
            'record' => $record,
            'lab_orders' => $labOrders,
            'attachments' => $attachments,
        ]);
    }

    #[Route('/medical-records/icd-codes', name: 'medical_records_icd_codes', methods: ['GET'])]
    public function getIcdCodes() : JsonResponse
    {
        $this->checkAuth();
        $this->gate->authorize('clinical.manage');

        $searchTerm = $_GET['search'] ?? '';
        $codes = $this->icdCodeRepository->searchByCodeOrDescription($searchTerm);

        return new JsonResponse($codes);
    }

    #[Route('/medical-records/intervention-codes', name: 'medical_records_intervention_codes', methods: ['GET'])]
    public function getInterventionCodes() : JsonResponse
    {
        $this->checkAuth();
        $this->gate->authorize('clinical.manage');

        $searchTerm = $_GET['search'] ?? '';
        $codes = $this->interventionCodeRepository->searchByCodeOrDescription($searchTerm);

        return new JsonResponse($codes);
    }

    #[Route('/medical-records/edit', name: 'medical_records_edit_get', methods: ['GET'])]
    public function edit() : Response
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($id);

        if (!$record) {
            return new Response("Медичний запис не знайдено", 404);
        }

        $this->gate->authorize('medical_record.edit', ['id' => $id]);

        $response = $this->render('@MedicalRecord/edit.html.twig', [
            'record' => $record,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/medical-records/edit', name: 'medical_records_edit_post', methods: ['POST'])]
    public function update() : Response
    {
        $this->checkAuth();
        $id = (int)($_POST['id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($id);

        if (!$record) {
            return new Response("Медичний запис не знайдено", 404);
        }

        $this->gate->authorize('medical_record.edit', ['id' => $id]);

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
            return new RedirectResponse('/medical-records/edit?id=' . $id);
        }

        $data = $_POST;
        $data['patient_id'] = $record['patient_id'];
        $data['appointment_id'] = $record['appointment_id'];
        $data['doctor_id'] = $record['doctor_id'];

        $this->medicalRecordRepository->update(
            $id,
            $data
        );

        return new RedirectResponse('/medical-records/show?id=' . $id);
    }

    #[Route('/medical-records/attachments/upload', name: 'medical_records_attachments_upload', methods: ['POST'])]
    public function uploadAttachment() : Response
    {
        $this->checkAuth();
        $medicalRecordId = (int)($_POST['medical_record_id'] ?? 0);
        $record = $this->medicalRecordRepository->findById($medicalRecordId);

        if (!$record) {
            return new Response("Медичний запис не знайдено", 404);
        }

        $this->gate->authorize('medical_record.edit', ['id' => $medicalRecordId]);

        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['name'] as $key => $name) {
                if (UPLOAD_ERR_OK === $_FILES['attachments']['error'][$key]) {
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
                        $this->gate->getUser()->getId()
                    );
                }
            }
        }

        return new RedirectResponse('/medical-records/show?id=' . $medicalRecordId);
    }

    #[Route('/medical-records/attachments/download', name: 'medical_records_attachments_download', methods: ['GET'])]
    public function downloadAttachment() : Response
    {
        $this->checkAuth();
        $attachmentId = (int)($_GET['attachment_id'] ?? 0);
        $attachment = $this->attachmentService->getAttachmentById($attachmentId);

        if (!$attachment || 'medical_record' !== $attachment['entity_type']) {
            return new Response("Вкладення не знайдено", 404);
        }

        $medicalRecordId = (int)$attachment['entity_id'];
        $record = $this->medicalRecordRepository->findById($medicalRecordId);

        if (!$record) {
            return new Response("Медичний запис, пов'язаний із вкладенням, не знайдено", 404);
        }

        $this->gate->authorize('medical_record.view', ['id' => $medicalRecordId]);

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
            return new Response("Файл не знайдено на сервері", 404);
        }

        $filename = $attachment['filename'];
        $mimeType = $attachment['mime_type'];
        $size = $attachment['size'];

        return new StreamedResponse(function () use ($fullPath) {
            readfile($fullPath);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => $size,
        ]);
    }
}

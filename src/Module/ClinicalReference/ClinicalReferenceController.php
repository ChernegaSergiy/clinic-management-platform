<?php

namespace App\Module\ClinicalReference;

use App\Module\ClinicalReference\Repository\IcdCodeRepository;
use App\Module\ClinicalReference\Repository\InterventionCodeRepository;
use MedCore\Nk0252021Parser\Parser;
use MedCore\Nk0262021Parser\Parser as Nk026Parser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ClinicalReferenceController extends \App\Core\Controller\AbstractController
{
    private IcdCodeRepository $icdCodeRepository;
    private InterventionCodeRepository $interventionCodeRepository;

    public function __construct(
        IcdCodeRepository $icdCodeRepository,
        InterventionCodeRepository $interventionCodeRepository
    ) {
        $this->icdCodeRepository = $icdCodeRepository;
        $this->interventionCodeRepository = $interventionCodeRepository;
    }

    #[Route('/admin/clinical/icd/import', name: 'clinical_icd_import_form', methods: ['GET'])]
    public function icdImportForm() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('clinical.manage');

        $count = $this->icdCodeRepository->countAll();
        $response = $this->render('@modules/ClinicalReference/templates/icd_import.html.twig', [
            'count' => $count,
            'errors' => $_SESSION['errors'] ?? [],
            'success_message' => $_SESSION['success_message'] ?? null,
        ]);
        unset($_SESSION['errors'], $_SESSION['success_message']);
        return $response;
    }

    #[Route('/admin/clinical/icd/import', name: 'clinical_icd_import_run', methods: ['POST'])]
    public function icdImportRun() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('clinical.manage');

        try {
            $parser = new Parser();
            $collection = $parser->parse();
            $rows = [];
            foreach ($collection as $item) {
                $code = $item->specific_code ?: $item->code;
                $description = $item->specific_name_ua ?: $item->name_ua ?: $item->name_en ?: $item->specific_name_en;
                if (!$code || !$description) {
                    continue;
                }
                $rows[] = [
                    'code' => $code,
                    'description' => $description,
                ];
            }

            if (empty($rows)) {
                throw new \RuntimeException('Не вдалося отримати дані класифікації.');
            }

            $inserted = $this->icdCodeRepository->replaceAll($rows);
            $_SESSION['success_message'] = sprintf('Імпортовано %d записів ICD-10 (NK-025-2021).', $inserted);
        } catch (\Throwable $e) {
            $_SESSION['errors']['import'] = $e->getMessage();
        }

        return new RedirectResponse('/admin/clinical');
    }

    #[Route('/admin/clinical/intervention/import', name: 'clinical_intervention_import_form', methods: ['GET'])]
    public function interventionImportForm() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('clinical.manage');

        $count = $this->interventionCodeRepository->countAll();
        $response = $this->render('@modules/ClinicalReference/templates/intervention_import.html.twig', [
            'count' => $count,
            'errors' => $_SESSION['errors'] ?? [],
            'success_message' => $_SESSION['success_message'] ?? null,
        ]);
        unset($_SESSION['errors'], $_SESSION['success_message']);
        return $response;
    }

    #[Route('/admin/clinical/intervention/import', name: 'clinical_intervention_import_run', methods: ['POST'])]
    public function interventionImportRun() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('clinical.manage');

        try {
            $parser = new Nk026Parser();
            $collection = $parser->parse();
            $rows = [];
            foreach ($collection as $item) {
                $code = $item->specific_code ?: $item->code;
                $description = $item->specific_name_ua ?: $item->name_ua ?: $item->name_en ?: $item->specific_name_en;
                if (!$code || !$description) {
                    continue;
                }
                $rows[] = [
                    'code' => $code,
                    'description' => $description,
                ];
            }

            if (empty($rows)) {
                throw new \RuntimeException('Не вдалося отримати дані класифікації.');
            }

            $inserted = $this->interventionCodeRepository->replaceAll($rows);
            $_SESSION['success_message'] = sprintf('Імпортовано %d записів НК 026:2021 '
                                                    . '(Класифікатор медичних інтервенцій).', $inserted);
        } catch (\Throwable $e) {
            $_SESSION['errors']['import'] = $e->getMessage();
        }

        return new RedirectResponse('/admin/clinical');
    }

    #[Route('/admin/clinical', name: 'clinical_index', methods: ['GET'])]
    public function clinicalIndex() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('clinical.manage');

        $icdCount = $this->icdCodeRepository->countAll();
        $interventionCount = $this->interventionCodeRepository->countAll();

        return $this->render('@modules/ClinicalReference/templates/index.html.twig', [
            'icdCount' => $icdCount,
            'interventionCount' => $interventionCount,
        ]);
    }
}

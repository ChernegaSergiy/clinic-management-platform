<?php

namespace App\Module\ClinicalReference;

use App\Core\Auth\Gate;
use App\Module\ClinicalReference\Repository\IcdCodeRepository;
use App\Module\ClinicalReference\Repository\InterventionCodeRepository;
use MedCore\Nk0252021Parser\Parser;
use MedCore\Nk0262021Parser\Parser as Nk026Parser;

class ClinicalReferenceController extends \App\Core\Controller\AbstractController
{
    private IcdCodeRepository $icdCodeRepository;
    private InterventionCodeRepository $interventionCodeRepository;

    public function __construct()
    {
        $this->icdCodeRepository = new IcdCodeRepository();
        $this->interventionCodeRepository = new InterventionCodeRepository();
    }

    public function icdImportForm(): void
    {
        $this->checkAuth();
        Gate::authorize('clinical.manage');

        $count = $this->icdCodeRepository->countAll();
        $this->render('@modules/ClinicalReference/templates/icd_import.html.twig', [
            'count' => $count,
            'errors' => $_SESSION['errors'] ?? [],
            'success_message' => $_SESSION['success_message'] ?? null,
        ]);
        unset($_SESSION['errors'], $_SESSION['success_message']);
    }

    public function icdImportRun(): void
    {
        $this->checkAuth();
        Gate::authorize('clinical.manage');

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

        header('Location: /admin/clinical');
        exit();
    }

    public function interventionImportForm(): void
    {
        $this->checkAuth();
        Gate::authorize('clinical.manage');

        $count = $this->interventionCodeRepository->countAll();
        $this->render('@modules/ClinicalReference/templates/intervention_import.html.twig', [
            'count' => $count,
            'errors' => $_SESSION['errors'] ?? [],
            'success_message' => $_SESSION['success_message'] ?? null,
        ]);
        unset($_SESSION['errors'], $_SESSION['success_message']);
    }

    public function interventionImportRun(): void
    {
        $this->checkAuth();
        Gate::authorize('clinical.manage');

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

        header('Location: /admin/clinical');
        exit();
    }

    public function clinicalIndex(): void
    {
        $this->checkAuth();
        Gate::authorize('clinical.manage');

        $icdCount = $this->icdCodeRepository->countAll();
        $interventionCount = $this->interventionCodeRepository->countAll();

        $this->render('@modules/ClinicalReference/templates/index.html.twig', [
            'icdCount' => $icdCount,
            'interventionCount' => $interventionCount,
        ]);
    }
}

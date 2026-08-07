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

namespace App\Bundles\KpiBundle\Controller;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepositoryInterface;
use App\Bundles\BillingBundle\Repository\InvoiceRepository;
use App\Bundles\KpiBundle\Repository\KpiRepository;
use App\Core\Validation\Validator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class KpiController extends \App\Core\Controller\AbstractController
{
    private KpiRepository $kpiRepository;
    private InvoiceRepository $invoiceRepository;
    private AppointmentRepositoryInterface $appointmentRepository;
    private Validator $validator;

    public function __construct(
        KpiRepository $kpiRepository,
        InvoiceRepository $invoiceRepository,
        AppointmentRepositoryInterface $appointmentRepository,
        Validator $validator
    ) {
        $this->kpiRepository = $kpiRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->validator = $validator;
    }

    // --- KPI Definitions ---
    #[Route('/kpi/definitions', name: 'kpi_definitions_index', methods: ['GET'])]
    public function listDefinitions() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('kpi.manage');
        $definitions = $this->kpiRepository->findAllKpiDefinitions();
        return $this->render('@Kpi/definitions/index.html.twig', ['definitions' => $definitions]);
    }

    #[Route('/kpi/definitions/new', name: 'kpi_definitions_new', methods: ['GET'])]
    public function createDefinition() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('kpi.manage');
        $response = $this->render('@Kpi/definitions/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/kpi/definitions/new', name: 'kpi_definitions_store', methods: ['POST'])]
    public function storeDefinition() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('kpi.manage');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required'],
            'kpi_type' => ['required', 'in:appointments_count,revenue_generated,patient_satisfaction'],
            'target_value' => ['numeric', 'min_value:0'],
            'unit' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse('/kpi/definitions/new');
        }

        $this->kpiRepository->saveKpiDefinition($_POST);
        $_SESSION['success_message'] = "Визначення KPI успішно додано.";
        return new RedirectResponse('/kpi/definitions');
    }

    #[Route('/kpi/definitions/edit', name: 'kpi_definitions_edit', methods: ['GET'])]
    public function editDefinition() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('kpi.manage');

        $id = (int)($_GET['id'] ?? 0);
        $definition = $this->kpiRepository->findKpiDefinitionById($id);

        if (!$definition) {
            return new Response("Визначення KPI не знайдено", 404);
        }

        $response = $this->render('@Kpi/definitions/edit.html.twig', [
            'definition' => $definition,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/kpi/definitions/edit', name: 'kpi_definitions_update', methods: ['POST'])]
    public function updateDefinition() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('kpi.manage');

        $id = (int)($_GET['id'] ?? 0);
        $definition = $this->kpiRepository->findKpiDefinitionById($id);

        if (!$definition) {
            return new Response("Визначення KPI не знайдено", 404);
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required'],
            'kpi_type' => ['required', 'in:appointments_count,revenue_generated,patient_satisfaction'],
            'target_value' => ['numeric', 'min_value:0'],
            'unit' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse('/kpi/definitions/edit?id=' . $id);
        }

        $this->kpiRepository->updateKpiDefinition($id, $_POST);
        $_SESSION['success_message'] = "Визначення KPI успішно оновлено.";
        return new RedirectResponse('/kpi/definitions');
    }

    #[Route('/kpi/definitions/delete', name: 'kpi_definitions_delete', methods: ['POST'])]
    public function deleteDefinition() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('kpi.manage');

        $id = (int)($_POST['id'] ?? 0);
        $this->kpiRepository->deleteKpiDefinition($id);
        $_SESSION['success_message'] = "Визначення KPI успішно видалено.";
        return new RedirectResponse('/kpi/definitions');
    }

    // --- KPI Results ---
    #[Route('/kpi/results', name: 'kpi_results_index', methods: ['GET'])]
    public function listResults() : Response
    {
        $this->checkAuth();
        $this->gate->authorize('kpi.read');
        $results = [];
        if (isset($_SESSION['user']) && 1 === $_SESSION['user']['role_id']) {
            // Перевірка, чи користувач є адміністратором
            $results = $this->kpiRepository->findAllKpiResults();
        } else {
            $userId = $_SESSION['user']['id'];
            $results = $this->kpiRepository->findKpiResultsForUser($userId);
        }
        return $this->render('@Kpi/results/index.html.twig', ['results' => $results]);
    }

    // This would be called by a cron job or background process
    #[Route('/kpi/calculate', name: 'kpi_calculate', methods: ['POST'])]
    public function calculateResults() : Response
    {
        $this->authorizeKpiAccess();
        $definitions = $this->kpiRepository->findActiveKpiDefinitions();
        $today = new \DateTimeImmutable('today');
        $userId = $_SESSION['user']['id'] ?? 1;

        foreach ($definitions as $definition) {
            $period = $definition['period'] ?? 'day';
            [$periodStart, $periodEnd] = $this->resolvePeriodRange($today, $period);

            $value = $this->calculateKpiValue($definition['kpi_type'], $periodStart, $periodEnd);
            if (null === $value) {
                continue;
            }
            $this->kpiRepository->saveKpiResult([
                'kpi_id' => $definition['id'],
                'user_id' => $userId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'calculated_value' => $value,
                'notes' => sprintf('Auto-calculated %s-%s', $periodStart, $periodEnd),
            ]);
        }

        $_SESSION['success_message'] = "KPI перераховано за " . $today->format('Y-m-d');
        return new RedirectResponse('/dashboard');
    }

    private function calculateKpiValue(string $type, string $from, string $to) : ?float
    {
        return match ($type) {
            'revenue_generated' => $this->invoiceRepository->sumRevenueForPeriod($from, $to),
            'appointments_count' => (float)$this->appointmentRepository->countScheduledByRange($from, $to),
            'doctor_utilization' => $this->calculateDoctorUtilization($from, $to),
            'readmission_rate' => $this->calculateReadmissionRate($from, $to),
            default => null, // Інші KPI не підтримані наразі
        };
    }

    private function resolvePeriodRange(\DateTimeImmutable $today, string $period) : array
    {
        return match ($period) {
            'week' => [
                $today->modify('-6 days')->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            'month' => [
                $today->modify('-29 days')->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            default => [
                $today->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
        };
    }

    private function calculateDoctorUtilization(string $from, string $to) : ?float
    {
        $bookedHours = $this->appointmentRepository->sumBookedHoursByRange($from, $to);
        $doctorCount = $this->appointmentRepository->countDistinctDoctorsByRange($from, $to);

        if (0 === $doctorCount) {
            return null;
        }

        $days = (new \DateTimeImmutable($from))->diff(new \DateTimeImmutable($to))->days + 1;
        $totalCapacity = $doctorCount * 8 * $days; // 8 робочих годин на лікаря

        if ($totalCapacity <= 0) {
            return null;
        }

        return round(($bookedHours / $totalCapacity) * 100, 1);
    }

    private function calculateReadmissionRate(string $from, string $to) : ?float
    {
        $totalPatients = $this->appointmentRepository->countDistinctPatientsByRange($from, $to);
        if (0 === $totalPatients) {
            return null;
        }

        $readmitted = $this->appointmentRepository->countReadmittedPatients($from, $to);

        return round(($readmitted / $totalPatients) * 100, 1);
    }

    private function authorizeKpiAccess() : void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        $this->checkAuth();
        $this->gate->authorize('admin.manage');
    }
}

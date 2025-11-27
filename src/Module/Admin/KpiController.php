<?php

namespace App\Module\Admin;

use App\Core\View;
use App\Core\Validator;
use App\Module\Admin\Repository\KpiRepository;
use App\Core\AuthGuard;
use App\Core\Gate;
use App\Module\Billing\Repository\InvoiceRepository;
use App\Module\Appointment\Repository\AppointmentRepository;

class KpiController
{
    private KpiRepository $kpiRepository;
    private InvoiceRepository $invoiceRepository;
    private AppointmentRepository $appointmentRepository;

    public function __construct()
    {
        $this->kpiRepository = new KpiRepository();
        $this->invoiceRepository = new InvoiceRepository();
        $this->appointmentRepository = new AppointmentRepository();
    }

    // --- KPI Definitions ---
    public function listDefinitions(): void
    {
        AuthGuard::check();
        Gate::authorize('kpi.manage');
        $definitions = $this->kpiRepository->findAllKpiDefinitions();
        View::render('@modules/Admin/templates/kpi/definitions/index.html.twig', ['definitions' => $definitions]);
    }

    public function createDefinition(): void
    {
        AuthGuard::check();
        Gate::authorize('kpi.manage');
        View::render('@modules/Admin/templates/kpi/definitions/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeDefinition(): void
    {
        AuthGuard::check();
        Gate::authorize('kpi.manage');

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'name' => ['required'],
            'kpi_type' => ['required', 'in:appointments_count,revenue_generated,patient_satisfaction'],
            'target_value' => ['numeric', 'min_value:0'],
            'unit' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /kpi/definitions/new');
            exit();
        }

        $this->kpiRepository->saveKpiDefinition($_POST);
        $_SESSION['success_message'] = "Визначення KPI успішно додано.";
        header('Location: /kpi/definitions');
        exit();
    }

    // --- KPI Results ---
    public function listResults(): void
    {
        AuthGuard::check();
        Gate::authorize('kpi.read');
        $results = [];
        if (isset($_SESSION['user']) && $_SESSION['user']['role_id'] === 1) {
            // Перевірка, чи користувач є адміністратором
            $results = $this->kpiRepository->findAllKpiResults();
        } else {
            $userId = $_SESSION['user']['id'];
            $results = $this->kpiRepository->findKpiResultsForUser($userId);
        }
        View::render('@modules/Admin/templates/kpi/results/index.html.twig', ['results' => $results]);
    }

    // This would be called by a cron job or background process
    public function calculateResults(): void
    {
        $this->authorizeAdmin();
        $definitions = $this->kpiRepository->findActiveKpiDefinitions();
        $today = new \DateTimeImmutable('today');
        $userId = $_SESSION['user']['id'] ?? null;

        foreach ($definitions as $definition) {
            $period = $definition['period'] ?? 'day';
            [$periodStart, $periodEnd] = $this->resolvePeriodRange($today, $period);

            $value = $this->calculateKpiValue($definition['kpi_type'], $periodStart, $periodEnd);
            if ($value === null) {
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
        header('Location: /dashboard');
        exit();
    }

    private function calculateKpiValue(string $type, string $from, string $to): ?float
    {
        return match ($type) {
            'revenue_generated' => $this->invoiceRepository->sumRevenueForPeriod($from, $to),
            'appointments_count' => (float)$this->appointmentRepository->countScheduledByRange($from, $to),
            default => null, // Інші KPI не підтримані наразі
        };
    }

    private function resolvePeriodRange(\DateTimeImmutable $today, string $period): array
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
}

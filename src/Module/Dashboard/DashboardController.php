<?php

namespace App\Module\Dashboard;

use App\Core\AuthGuard;
use App\Core\View;
use App\Module\Dashboard\Service\DashboardService;

class DashboardController
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    public function index(): void
    {
        AuthGuard::check(); // Ensure user is authenticated

        $dashboardData = $this->dashboardService->getDashboardData();

        View::render('dashboard/index.html.twig', [
            'dashboardData' => $dashboardData,
        ]);
    }
}
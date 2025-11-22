<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\PatientRepository;
use App\Repository\AppointmentRepository;
use App\Repository\InventoryItemRepository;
use App\Core\AuthGuard;

class DashboardController
{
    private PatientRepository $patientRepository;
    private AppointmentRepository $appointmentRepository;
    private InventoryItemRepository $inventoryItemRepository;

    public function __construct()
    {
        $this->patientRepository = new PatientRepository();
        $this->appointmentRepository = new AppointmentRepository();
        $this->inventoryItemRepository = new InventoryItemRepository();
    }

    public function index(): void
    {
        AuthGuard::check();

        // Fetch data for dashboard widgets
        $patientCount = count($this->patientRepository->findAllActive());
        $upcomingAppointments = $this->appointmentRepository->findUpcoming(); 
        $upcomingAppointmentsCount = count($upcomingAppointments);
        $lowStockItems = $this->inventoryItemRepository->findItemsBelowMinStock();
        $lowStockItemsCount = count($lowStockItems);

        View::render('dashboard/index.html.twig', [
            'patient_count' => $patientCount,
            'upcoming_appointments' => $upcomingAppointments,
            'upcoming_appointments_count' => $upcomingAppointmentsCount,
            'low_stock_items' => $lowStockItems,
            'low_stock_items_count' => $lowStockItemsCount,
        ]);
    }
}

<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\PatientRepository;
use App\Repository\AppointmentRepository;
use App\Repository\InventoryItemRepository;

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
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        // Fetch data for dashboard widgets
        $patientCount = count($this->patientRepository->findAllActive());
        $upcomingAppointmentsCount = count($this->appointmentRepository->findUpcoming()); // Need to implement this method
        $lowStockItemsCount = count($this->inventoryItemRepository->findLowStock()); // Need to implement this method

        View::render('dashboard/index.html.twig', [
            'patient_count' => $patientCount,
            'upcoming_appointments_count' => $upcomingAppointmentsCount,
            'low_stock_items_count' => $lowStockItemsCount,
        ]);
    }
}

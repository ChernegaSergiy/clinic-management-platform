<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\AppointmentRepository;

class AppointmentController
{
    private AppointmentRepository $appointmentRepository;

    public function __construct()
    {
        $this->appointmentRepository = new AppointmentRepository();
    }

    public function index(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        $appointments = $this->appointmentRepository->findAll();
        View::render('appointments/index.html.twig', ['appointments' => $appointments]);
    }
}

<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\PatientRepository;

class PatientController
{
    private PatientRepository $patientRepository;

    public function __construct()
    {
        $this->patientRepository = new PatientRepository();
    }

    public function index(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        $patients = $this->patientRepository->findAll();
        View::render('patients/index.html.twig', ['patients' => $patients]);
    }

    public function create(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        View::render('patients/new.html.twig');
    }

    public function store(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        $this->patientRepository->save($_POST);
        header('Location: /patients');
        exit();
    }
}

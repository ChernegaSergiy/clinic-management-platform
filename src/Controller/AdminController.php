<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\UserRepository;

class AdminController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function users(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) { // Assuming role_id 1 is admin
            header('Location: /login');
            exit();
        }
        $users = $this->userRepository->findAll();
        View::render('admin/users.html.twig', ['users' => $users]);
    }
}

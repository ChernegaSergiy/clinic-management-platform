<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;

class AdminController
{
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->roleRepository = new RoleRepository();
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

    public function createUser(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) { // Assuming role_id 1 is admin
            header('Location: /login');
            exit();
        }

        $roles = $this->roleRepository->findAll();
        $roleOptions = [];
        foreach ($roles as $role) {
            $roleOptions[$role['id']] = $role['name'];
        }

        View::render('admin/new_user.html.twig', ['roles' => $roleOptions]);
    }

    public function storeUser(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) { // Assuming role_id 1 is admin
            header('Location: /login');
            exit();
        }

        // TODO: Add validation
        $this->userRepository->save($_POST);
        header('Location: /admin/users');
        exit();
    }

    public function showUser(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) { // Assuming role_id 1 is admin
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            http_response_code(404);
            echo "Користувача не знайдено";
            return;
        }

        View::render('admin/show_user.html.twig', ['user' => $user]);
    }
}

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
        $roles = $this->roleRepository->findAll();
        $roleMap = [];
        foreach ($roles as $role) {
            $roleMap[$role['id']] = $role['name'];
        }

        foreach ($users as &$user) {
            $user['role_name'] = $roleMap[$user['role_id']] ?? 'Невідома';
        }
        unset($user); // Break the reference with the last element

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
        $_SESSION['success_message'] = "Користувача успішно створено.";
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

        $role = $this->roleRepository->findById($user['role_id']);
        $user['role_name'] = $role['name'] ?? 'Невідома';

        View::render('admin/show_user.html.twig', ['user' => $user]);
    }

    public function editUser(): void
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

        $roles = $this->roleRepository->findAll();
        $roleOptions = [];
        foreach ($roles as $role) {
            $roleOptions[$role['id']] = $role['name'];
        }

        View::render('admin/edit_user.html.twig', [
            'user' => $user,
            'roles' => $roleOptions,
        ]);
    }

    public function updateUser(): void
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

        // TODO: Add validation
        $this->userRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Дані користувача успішно оновлено.";
        header('Location: /admin/users/show?id=' . $id);
        exit();
    }

    public function deleteUser(): void
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

        // Prevent admin from deleting themselves
        if ($user['id'] === $_SESSION['user']['id']) {
            $_SESSION['error_message'] = "Ви не можете видалити свій власний обліковий запис.";
            header('Location: /admin/users');
            exit();
        }

        $this->userRepository->delete($id);
        $_SESSION['success_message'] = "Користувача успішно видалено.";
        header('Location: /admin/users');
        exit();
    }
}

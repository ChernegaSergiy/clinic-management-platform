<?php

namespace App\Module\Hrm;

use App\Core\View;
use App\Module\Hrm\Repository\HrmRepository;
use App\Module\User\Repository\UserRepository;
use App\Core\AuthGuard;
use App\Core\Gate;

class HrmController
{
    private HrmRepository $hrmRepository;
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->hrmRepository = new HrmRepository();
        $this->userRepository = new UserRepository();
    }

    public function index(): void
    {
        AuthGuard::check();
        Gate::authorize('hrm.read');

        $employees = $this->hrmRepository->findAll();

        View::render('@modules/HRM/templates/index.html.twig', [
            'employees' => $employees,
        ]);
    }

    public function create(): void
    {
        AuthGuard::check();
        Gate::authorize('hrm.write');
        
        $users = $this->userRepository->findAll();

        View::render('@modules/HRM/templates/new.html.twig', [
            'users' => $users,
        ]);
    }

    public function store(): void
    {
        AuthGuard::check();
        Gate::authorize('hrm.write');

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $rules = [
            'first_name' => ['required'],
            'last_name' => ['required'],
            'position' => ['required'],
            'hire_date' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            // Re-fetch users for the form
            $users = $this->userRepository->findAll();
            View::render('@modules/HRM/templates/new.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'users' => $users,
            ]);
            return;
        }
        
        if ($this->hrmRepository->save($_POST)) {
            $_SESSION['success_message'] = 'Співробітника успішно додано.';
        } else {
            $_SESSION['error_message'] = 'Не вдалося додати співробітника.';
        }

        header('Location: /hrm');
        exit();
    }

    public function show(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('hrm.read');

        $employee = $this->hrmRepository->findById($id);

        if (!$employee) {
            http_response_code(404);
            echo "Співробітника не знайдено";
            return;
        }

        View::render('@modules/HRM/templates/show.html.twig', [
            'employee' => $employee,
        ]);
    }

    public function edit(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('hrm.write');

        $employee = $this->hrmRepository->findById($id);

        if (!$employee) {
            http_response_code(404);
            echo "Співробітника не знайдено";
            return;
        }

        $users = $this->userRepository->findAll();

        View::render('@modules/HRM/templates/edit.html.twig', [
            'employee' => $employee,
            'users' => $users,
        ]);
    }

    public function update(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('hrm.write');

        $employee = $this->hrmRepository->findById($id);

        if (!$employee) {
            http_response_code(404);
            echo "Співробітника не знайдено";
            return;
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $rules = [
            'first_name' => ['required'],
            'last_name' => ['required'],
            'position' => ['required'],
            'hire_date' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
             // Re-fetch users for the form
            $users = $this->userRepository->findAll();
            View::render('@modules/HRM/templates/edit.html.twig', [
                'errors' => $validator->getErrors(),
                'employee' => array_merge($employee, $_POST),
                'users' => $users,
            ]);
            return;
        }

        if ($this->hrmRepository->update($id, $_POST)) {
            $_SESSION['success_message'] = 'Дані співробітника оновлено.';
        } else {
            $_SESSION['error_message'] = 'Не вдалося оновити дані.';
        }

        header('Location: /hrm/show?id=' . $id);
        exit();
    }

    public function toggleStatus(): void
    {
        AuthGuard::check();
        Gate::authorize('hrm.manage');
        
        $id = (int)($_POST['id'] ?? 0);
        $employee = $this->hrmRepository->findById($id);

        if ($employee) {
            $newStatus = $employee['status'] === 'active' ? 'terminated' : 'active';
            $this->hrmRepository->updateStatus($id, $newStatus);
            $_SESSION['success_message'] = 'Статус співробітника оновлено.';
        }

        header('Location: /hrm/show?id=' . $id);
        exit();
    }
}

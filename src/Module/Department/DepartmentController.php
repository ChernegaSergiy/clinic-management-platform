<?php

namespace App\Module\Department;

use App\Database\Database;
use App\Core\Auth\AuthGuard;
use App\Core\Auth\Gate;
use App\Core\Http\View;
use App\Module\Department\Repository\DepartmentRepository;
use App\Module\Hrm\Repository\HrmRepository;

class DepartmentController
{
    private DepartmentRepository $departmentRepository;
    private HrmRepository $hrmRepository;

    public function __construct()
    {
        $this->departmentRepository = new DepartmentRepository();
        $this->hrmRepository = new HrmRepository();
    }

    public function index(): void
    {
        AuthGuard::check();
        Gate::authorize('department.read');

        $departments = $this->departmentRepository->findAll();

        View::render('@modules/Department/templates/index.html.twig', [
            'departments' => $departments,
        ]);
    }

    public function create(): void
    {
        AuthGuard::check();
        Gate::authorize('department.write');

        $departments = $this->departmentRepository->findAll();
        $parentOptions = array_filter($departments, fn($dept) => $dept['parent_id'] === null);

        View::render('@modules/Department/templates/new.html.twig', [
            'parentOptions' => $parentOptions,
        ]);
    }

    public function store(): void
    {
        AuthGuard::check();
        Gate::authorize('department.write');

        $validator = new \App\Core\Validation\Validator(Database::getInstance());
        $rules = [
            'name' => ['required'],
            'description' => [],
            'parent_id' => ['integer'],
            'is_active' => [],
            'sort_order' => ['integer'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            $departments = $this->departmentRepository->findAll();
            $parentOptions = array_filter($departments, fn($dept) => $dept['parent_id'] === null);

            View::render('@modules/Department/templates/new.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'parentOptions' => $parentOptions,
            ]);
            return;
        }

        if ($this->departmentRepository->save($_POST)) {
            $_SESSION['success_message'] = 'Відділ успішно створено.';
        } else {
            $_SESSION['error_message'] = 'Не вдалося створити відділ.';
        }

        header('Location: /admin/departments');
        exit();
    }

    public function show(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('department.read');

        $department = $this->departmentRepository->findById($id);

        if (!$department) {
            http_response_code(404);
            echo "Відділ не знайдено";
            return;
        }

        // Отримуємо співробітників цього відділу
        $employees = $this->hrmRepository->findByDepartment($id);

        View::render('@modules/Department/templates/show.html.twig', [
            'department' => $department,
            'employees' => $employees,
        ]);
    }

    public function edit(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('department.write');

        $department = $this->departmentRepository->findById($id);

        if (!$department) {
            http_response_code(404);
            echo "Відділ не знайдено";
            return;
        }

        $departments = $this->departmentRepository->findAll();
        $parentOptions = array_filter($departments, fn($dept) => $dept['parent_id'] === null);

        View::render('@modules/Department/templates/edit.html.twig', [
            'department' => $department,
            'parentOptions' => $parentOptions,
        ]);
    }

    public function update(): void
    {
        AuthGuard::check();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('department.write');

        $department = $this->departmentRepository->findById($id);

        if (!$department) {
            http_response_code(404);
            echo "Відділ не знайдено";
            return;
        }

        $validator = new \App\Core\Validation\Validator(Database::getInstance());
        $rules = [
            'name' => ['required'],
            'description' => [],
            'parent_id' => ['integer'],
            'is_active' => [],
            'sort_order' => ['integer'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            $departments = $this->departmentRepository->findAll();
            $parentOptions = array_filter($departments, fn($dept) => $dept['parent_id'] === null);

            View::render('@modules/Department/templates/edit.html.twig', [
                'errors' => $validator->getErrors(),
                'department' => array_merge($department, $_POST),
                'parentOptions' => $parentOptions,
            ]);
            return;
        }

        if ($this->departmentRepository->update($id, $_POST)) {
            $_SESSION['success_message'] = 'Дані відділу оновлено.';
        } else {
            $_SESSION['error_message'] = 'Не вдалося оновити дані.';
        }

        header('Location: /admin/departments/show?id=' . $id);
        exit();
    }

    public function delete(): void
    {
        AuthGuard::check();
        Gate::authorize('department.delete');

        $id = (int)($_POST['id'] ?? 0);
        $department = $this->departmentRepository->findById($id);

        if ($department) {
            if ($this->departmentRepository->delete($id)) {
                $_SESSION['success_message'] = 'Відділ видалено.';
            } else {
                $_SESSION['error_message'] = 'Не вдалося видалити відділ. Можливо, є дочірні відділи.';
            }
        }

        header('Location: /admin/departments');
        exit();
    }

    public function toggleStatus(): void
    {
        AuthGuard::check();
        Gate::authorize('department.manage');

        $id = (int)($_POST['id'] ?? 0);
        $department = $this->departmentRepository->findById($id);

        if ($department) {
            $newStatus = $department['is_active'] ? 0 : 1;
            $this->departmentRepository->update($id, ['is_active' => $newStatus]);
            $_SESSION['success_message'] = 'Статус відділу оновлено.';
        }

        header('Location: /admin/departments');
        exit();
    }
}
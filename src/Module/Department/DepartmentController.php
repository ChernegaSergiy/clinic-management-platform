<?php

namespace App\Module\Department;

use App\Database\Database;
use App\Core\Auth\Gate;
use App\Module\Department\Repository\DepartmentRepository;
use App\Module\Hrm\Repository\HrmRepositoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class DepartmentController extends \App\Core\Controller\AbstractController
{
    private DepartmentRepository $departmentRepository;
    private HrmRepositoryInterface $hrmRepository;
    private \App\Core\Validation\Validator $validator;

    public function __construct(
        DepartmentRepository $departmentRepository,
        HrmRepositoryInterface $hrmRepository,
        \App\Core\Validation\Validator $validator
    ) {
        $this->departmentRepository = $departmentRepository;
        $this->hrmRepository = $hrmRepository;
        $this->validator = $validator;
    }

    #[Route('/admin/departments', name: 'admin_departments_index', methods: ['GET'])]
    public function index(): void
    {
        $this->checkAuth();
        Gate::authorize('department.read');

        $departments = $this->departmentRepository->findAll();

        $this->render('@modules/Department/templates/index.html.twig', [
            'departments' => $departments,
        ]);
    }

    #[Route('/admin/departments/new', name: 'admin_departments_new', methods: ['GET'])]
    public function create(): void
    {
        $this->checkAuth();
        Gate::authorize('department.write');

        $departments = $this->departmentRepository->findAll();
        $parentOptions = array_filter($departments, fn($dept) => $dept['parent_id'] === null);

        $this->render('@modules/Department/templates/new.html.twig', [
            'parentOptions' => $parentOptions,
        ]);
    }

    #[Route('/admin/departments/new', name: 'admin_departments_new_post', methods: ['POST'])]
    public function store(): void
    {
        $this->checkAuth();
        Gate::authorize('department.write');

        $validator = $this->validator;
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

            $this->render('@modules/Department/templates/new.html.twig', [
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

    #[Route('/admin/departments/show', name: 'admin_departments_show', methods: ['GET'])]
    public function show(): void
    {
        $this->checkAuth();
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

        $this->render('@modules/Department/templates/show.html.twig', [
            'department' => $department,
            'employees' => $employees,
        ]);
    }

    #[Route('/admin/departments/edit', name: 'admin_departments_edit', methods: ['GET'])]
    public function edit(): void
    {
        $this->checkAuth();
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

        $this->render('@modules/Department/templates/edit.html.twig', [
            'department' => $department,
            'parentOptions' => $parentOptions,
        ]);
    }

    #[Route('/admin/departments/edit', name: 'admin_departments_edit_post', methods: ['POST'])]
    public function update(): void
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        Gate::authorize('department.write');

        $department = $this->departmentRepository->findById($id);

        if (!$department) {
            http_response_code(404);
            echo "Відділ не знайдено";
            return;
        }

        $validator = $this->validator;
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

            $this->render('@modules/Department/templates/edit.html.twig', [
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

    #[Route('/admin/departments/delete', name: 'admin_departments_delete', methods: ['POST'])]
    public function delete(): void
    {
        $this->checkAuth();
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

    #[Route('/admin/departments/toggle-status', name: 'admin_departments_toggle_status', methods: ['POST'])]
    public function toggleStatus(): void
    {
        $this->checkAuth();
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

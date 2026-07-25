<?php

namespace App\Module\Hrm;

use App\Database\Database;

use App\Module\Hrm\Repository\HrmRepositoryInterface;
use App\Module\Department\Repository\DepartmentRepository;
use App\Module\User\Repository\UserRepositoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Core\Validation\Validator;

class HrmController extends \App\Core\Controller\AbstractController
{
    private HrmRepositoryInterface $hrmRepository;
    private UserRepositoryInterface $userRepository;
    private DepartmentRepository $departmentRepository;
    private Validator $validator;

    public function __construct(
        HrmRepositoryInterface $hrmRepository,
        UserRepositoryInterface $userRepository,
        DepartmentRepository $departmentRepository,
        Validator $validator
    ) {
        $this->hrmRepository = $hrmRepository;
        $this->userRepository = $userRepository;
        $this->departmentRepository = $departmentRepository;
        $this->validator = $validator;
    }

    #[Route('/hrm', name: 'hrm_index', methods: ['GET'])]
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('hrm.read');

        $employees = $this->hrmRepository->findAll();

        return $this->render('@modules/Hrm/templates/index.html.twig', [
            'employees' => $employees,
        ]);
    }

    #[Route('/hrm/new', name: 'hrm_new', methods: ['GET'])]
    public function create(): \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('hrm.write');

        $users = $this->userRepository->findAll();
        $departments = $this->departmentRepository->findAllActive();

        return $this->render('@modules/Hrm/templates/new.html.twig', [
            'users' => $users,
            'departments' => $departments,
        ]);
    }

    #[Route('/hrm/new', name: 'hrm_store', methods: ['POST'])]
    public function store(): \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('hrm.write');

        $validator = $this->validator;
        $rules = [
            'first_name' => ['required'],
            'last_name' => ['required'],
            'position' => ['required'],
            'hire_date' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            // Re-fetch users for the form
            $users = $this->userRepository->findAll();
            return $this->render('@modules/Hrm/templates/new.html.twig', [
                'errors' => $validator->getErrors(),
                'old' => $_POST,
                'users' => $users,
            ]);
        }

        if ($this->hrmRepository->save($_POST)) {
            $_SESSION['success_message'] = 'Співробітника успішно додано.';
        } else {
            $_SESSION['error_message'] = 'Не вдалося додати співробітника.';
        }

        return new \Symfony\Component\HttpFoundation\RedirectResponse('/hrm');
    }

    #[Route('/hrm/show', name: 'hrm_show', methods: ['GET'])]
    public function show(): \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        $this->gate->authorize('hrm.read');

        $employee = $this->hrmRepository->findById($id);

        if (!$employee) {
            return new \Symfony\Component\HttpFoundation\Response("Співробітника не знайдено", 404);
        }

        return $this->render('@modules/Hrm/templates/show.html.twig', [
            'employee' => $employee,
        ]);
    }

    #[Route('/hrm/edit', name: 'hrm_edit', methods: ['GET'])]
    public function edit(): \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        $this->gate->authorize('hrm.write');

        $employee = $this->hrmRepository->findById($id);

        if (!$employee) {
            return new \Symfony\Component\HttpFoundation\Response("Співробітника не знайдено", 404);
        }

        $users = $this->userRepository->findAll();
        $departments = $this->departmentRepository->findAllActive();

        return $this->render('@modules/Hrm/templates/edit.html.twig', [
            'employee' => $employee,
            'users' => $users,
            'departments' => $departments,
        ]);
    }

    #[Route('/hrm/edit', name: 'hrm_update', methods: ['POST'])]
    public function update(): \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $id = (int)($_GET['id'] ?? 0);
        $this->gate->authorize('hrm.write');

        $employee = $this->hrmRepository->findById($id);

        if (!$employee) {
            return new \Symfony\Component\HttpFoundation\Response("Співробітника не знайдено", 404);
        }

        $validator = $this->validator;
        $rules = [
            'first_name' => ['required'],
            'last_name' => ['required'],
            'position' => ['required'],
            'hire_date' => ['required'],
        ];

        if (!$validator->validate($_POST, $rules)) {
            // Re-fetch users for the form
            $users = $this->userRepository->findAll();
            return $this->render('@modules/Hrm/templates/edit.html.twig', [
                    'errors' => $validator->getErrors(),
                    'employee' => array_merge($employee, $_POST),
                    'users' => $users,
                ]);
        }

        if ($this->hrmRepository->update($id, $_POST)) {
            $_SESSION['success_message'] = 'Дані співробітника оновлено.';
        } else {
            $_SESSION['error_message'] = 'Не вдалося оновити дані.';
        }

        return new \Symfony\Component\HttpFoundation\RedirectResponse('/hrm/show?id=' . $id);
    }

    #[Route('/hrm/toggle-status', name: 'hrm_toggle_status', methods: ['POST'])]
    public function toggleStatus(): \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();
        $this->gate->authorize('hrm.manage');

        $id = (int)($_POST['id'] ?? 0);
        $employee = $this->hrmRepository->findById($id);

        if ($employee) {
            $newStatus = $employee['status'] === 'active' ? 'terminated' : 'active';
            $this->hrmRepository->updateStatus($id, $newStatus);
            $_SESSION['success_message'] = 'Статус співробітника оновлено.';
        }

        return new \Symfony\Component\HttpFoundation\RedirectResponse('/hrm/show?id=' . $id);
    }
}

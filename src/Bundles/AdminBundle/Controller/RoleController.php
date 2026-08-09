<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Bundles\AdminBundle\Controller;

use App\Bundles\UserBundle\Repository\RoleRepositoryInterface;
use App\Core\Validation\Validator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RoleController extends AbstractController
{
    private RoleRepositoryInterface $roleRepository;
    private Validator $validator;

    public function __construct(RoleRepositoryInterface $roleRepository, Validator $validator)
    {
        $this->roleRepository = $roleRepository;
        $this->validator = $validator;
    }

    #[Route('/roles', name: 'admin_roles', methods: ['GET'])]
    public function listRoles() : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_MANAGE');
        $roles = $this->roleRepository->findAll();
        return $this->render('@Admin/roles/index.html.twig', ['roles' => $roles]);
    }

    #[Route('/roles/new', name: 'admin_roles_new', methods: ['GET'])]
    public function createRole() : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_MANAGE');
        $response = $this->render('@Admin/roles/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/roles/new', name: 'admin_roles_new_post', methods: ['POST'])]
    public function storeRole() : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_MANAGE');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:roles'], // Need to implement unique validation in Validator
            'description' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse('/admin/roles/new');
        }

        $this->roleRepository->save($_POST);
        $_SESSION['success_message'] = "Роль успішно створено.";
        return new RedirectResponse('/admin/roles');
    }

    #[Route('/roles/edit', name: 'admin_roles_edit', methods: ['GET'])]
    public function editRole() : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_MANAGE');

        $id = (int)($_GET['id'] ?? 0);
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            return new Response("Роль не знайдено", 404);
        }

        $response = $this->render('@Admin/roles/edit.html.twig', [
            'role' => $role,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/roles/edit', name: 'admin_roles_edit_post', methods: ['POST'])]
    public function updateRole() : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_MANAGE');

        $id = (int)($_GET['id'] ?? 0);
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            return new Response("Роль не знайдено", 404);
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:roles,name,' . $id], // Need to implement unique validation in Validator
            'description' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new RedirectResponse('/admin/roles/edit?id=' . $id);
        }

        $this->roleRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Роль успішно оновлено.";
        return new RedirectResponse('/admin/roles');
    }

    #[Route('/roles/delete', name: 'admin_roles_delete', methods: ['POST'])]
    public function deleteRole() : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_MANAGE');

        $id = (int)($_POST['id'] ?? 0);
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            return new Response("Роль не знайдено", 404);
        }

        $this->roleRepository->delete($id);
        $_SESSION['success_message'] = "Роль успішно видалено.";
        return new RedirectResponse('/admin/roles');
    }
}

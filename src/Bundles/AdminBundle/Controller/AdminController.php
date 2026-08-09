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

use App\Bundles\AdminBundle\Repository\BackupPolicyRepository;
use App\Bundles\BillingBundle\Repository\ServiceRepository;
use App\Bundles\UserBundle\Repository\RoleRepositoryInterface;
use App\Core\Repository\SettingsRepository;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends \App\Core\Controller\AbstractController
{
    private RoleRepositoryInterface $roleRepository;
    private BackupPolicyRepository $backupPolicyRepository;
    private ServiceRepository $serviceRepository;
    private SettingsRepository $settingsRepository;
    private \App\Core\Validation\Validator $validator;

    public function __construct(
        RoleRepositoryInterface $roleRepository,
        BackupPolicyRepository $backupPolicyRepository,
        ServiceRepository $serviceRepository,
        SettingsRepository $settingsRepository,
        \App\Core\Validation\Validator $validator
    ) {
        $this->roleRepository = $roleRepository;
        $this->backupPolicyRepository = $backupPolicyRepository;
        $this->serviceRepository = $serviceRepository;
        $this->settingsRepository = $settingsRepository;
        $this->validator = $validator;
    }

    #[Route('/settings', name: 'admin_settings', methods: ['GET'])]
    public function showSettings() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();

        $roles = $this->roleRepository->findAll();

        $settings = [
            'clinic_name' => $this->settingsRepository->get('clinic_name', ''),
            'mfa_policy' => $this->settingsRepository->getMfaPolicy(),
            'mfa_force_roles' => $this->settingsRepository->getMfaForceRoles(),
            'system_locale' => $this->settingsRepository->get('system_locale', 'uk'),
        ];

        $availableLocales = $this->view->getTranslationService()->getAvailableLocales();

        return $this->render('@Admin/settings.html.twig', [
            'settings' => $settings,
            'roles' => $roles,
            'availableLocales' => $availableLocales,
        ]);
    }

    #[Route('/settings', name: 'admin_settings_post', methods: ['POST'])]
    public function updateSettings() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();

        $clinicName = $_POST['clinic_name'] ?? '';
        $mfaPolicy = $_POST['mfa_policy'] ?? 'optional';
        $mfaForceRolesRaw = $_POST['mfa_force_roles'] ?? '';
        $locale = $_POST['locale'] ?? 'uk';

        $mfaForceRoles = [];
        if (!empty($mfaForceRolesRaw)) {
            $mfaForceRoles = array_map('intval', explode(',', $mfaForceRolesRaw));
        }

        $this->settingsRepository->set('clinic_name', $clinicName);
        $this->settingsRepository->setMfaPolicy($mfaPolicy);
        $this->settingsRepository->setMfaForceRoles($mfaForceRoles);
        $this->settingsRepository->set('system_locale', $locale);

        // A new View instance is built for every request, so the updated
        // locale takes effect automatically on the next request.
        $_SESSION['success_message'] = 'Налаштування збережено.';
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/settings');
    }

    // --- Backup Policy Management ---
    #[Route('/backup-policies', name: 'admin_backup_policies', methods: ['GET'])]
    public function listBackupPolicies() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $policies = $this->backupPolicyRepository->findAll();
        return $this->render('@Admin/backup_policies/index.html.twig', ['policies' => $policies]);
    }

    #[Route('/backup-policies/new', name: 'admin_backup_policies_new', methods: ['GET'])]
    public function createBackupPolicy() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $response = $this->render('@Admin/backup_policies/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/backup-policies/new', name: 'admin_backup_policies_new_post', methods: ['POST'])]
    public function storeBackupPolicy() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:backup_policies,name'],
            'frequency' => ['required'],
            'retention_days' => ['required', 'numeric', 'min:1'],
            'status' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/backup_policies/new');
        }

        $this->backupPolicyRepository->save($_POST);
        $_SESSION['success_message'] = "Політику резервного копіювання успішно створено.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/backup_policies');
    }

    #[Route('/backup-policies/edit', name: 'admin_backup_policies_edit', methods: ['GET'])]
    public function editBackupPolicy() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $policy = $this->backupPolicyRepository->findById($id);

        if (!$policy) {
            return new \Symfony\Component\HttpFoundation\Response("Політику резервного копіювання не знайдено", 404);
        }

        $response = $this->render('@Admin/backup_policies/edit.html.twig', [
            'policy' => $policy,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/backup-policies/edit', name: 'admin_backup_policies_edit_post', methods: ['POST'])]
    public function updateBackupPolicy() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $policy = $this->backupPolicyRepository->findById($id);

        if (!$policy) {
            return new \Symfony\Component\HttpFoundation\Response("Політику резервного копіювання не знайдено", 404);
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:backup_policies,name,' . $id],
            'frequency' => ['required'],
            'retention_days' => ['required', 'numeric', 'min:1'],
            'status' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/backup_policies/edit?id=' . $id);
        }

        $this->backupPolicyRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Політику резервного копіювання успішно оновлено.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/backup_policies');
    }

    #[Route('/backup-policies/delete', name: 'admin_backup_policies_delete', methods: ['POST'])]
    public function deleteBackupPolicy() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $this->backupPolicyRepository->delete($id);
        $_SESSION['success_message'] = "Політику резервного копіювання успішно видалено.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/backup_policies');
    }


    // --- Service Management ---
    #[Route('/services', name: 'admin_services', methods: ['GET'])]
    public function listServices() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $this->gate->authorize('admin.manage_services'); // Specific permission for service management
        $services = $this->serviceRepository->findAll();
        return $this->render('@Admin/services/index.html.twig', ['services' => $services]);
    }

    #[Route('/services/new', name: 'admin_services_new', methods: ['GET'])]
    public function createService() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $this->gate->authorize('admin.manage_services');
        $categories = $this->serviceRepository->findCategories();
        $categoryOptions = [];
        foreach ($categories as $category) {
            $categoryOptions[$category['id']] = $category['name'];
        }

        $response = $this->render('@Admin/users/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'categories' => $categoryOptions,
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/services/new', name: 'admin_services_new_post', methods: ['POST'])]
    public function storeService() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $this->gate->authorize('admin.manage_services');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:services,name'],
            'price' => ['required', 'numeric'],
            'duration_minutes' => ['required', 'numeric', 'min:1'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/services/new');
        }

        // Normalize is_active checkbox value
        $_POST['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        $this->serviceRepository->save($_POST);
        $_SESSION['success_message'] = "Послугу успішно створено.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/services');
    }

    #[Route('/services/edit', name: 'admin_services_edit', methods: ['GET'])]
    public function editService() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $this->gate->authorize('admin.manage_services');

        $id = (int)($_GET['id'] ?? 0);
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            return new \Symfony\Component\HttpFoundation\Response("Послугу не знайдено", 404);
        }

        $categories = $this->serviceRepository->findCategories();
        $categoryOptions = [];
        foreach ($categories as $category) {
            $categoryOptions[$category['id']] = $category['name'];
        }

        $response = $this->render('@Admin/users/edit.html.twig', [
            'service' => $service,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'categories' => $categoryOptions,
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/services/edit', name: 'admin_services_edit_post', methods: ['POST'])]
    public function updateService() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $this->gate->authorize('admin.manage_services');

        $id = (int)($_GET['id'] ?? 0);
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            return new \Symfony\Component\HttpFoundation\Response("Послугу не знайдено", 404);
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:services,name,' . $id],
            'price' => ['required', 'numeric'],
            'duration_minutes' => ['required', 'numeric', 'min:1'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/services/edit?id=' . $id);
        }

        // Normalize is_active checkbox value
        $_POST['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        $this->serviceRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Послугу успішно оновлено.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/services');
    }

    #[Route('/services/delete', name: 'admin_services_delete', methods: ['POST'])]
    public function deleteService() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $this->gate->authorize('admin.manage_services');

        $id = (int)($_POST['id'] ?? 0);
        $this->serviceRepository->delete($id);
        $_SESSION['success_message'] = "Послугу успішно видалено.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/services');
    }

    // --- Service Category Management ---
    #[Route('/service-categories', name: 'admin_service_categories', methods: ['GET'])]
    public function listServiceCategories() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $this->gate->authorize('admin.manage_service_categories');
        $categories = $this->serviceRepository->findCategories();
        return $this->render('@Admin/service_categories/index.html.twig', ['categories' => $categories]);
    }

    #[Route('/service-categories/new', name: 'admin_service_categories_new', methods: ['GET'])]
    public function createServiceCategory() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $this->gate->authorize('admin.manage_service_categories');
        $response = $this->render('@Admin/service_categories/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/service-categories/new', name: 'admin_service_categories_new_post', methods: ['POST'])]
    public function storeServiceCategory() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $this->gate->authorize('admin.manage_service_categories');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:service_categories,name'],
            'description' => [], // Optional
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/service-categories/new');
        }

        $this->serviceRepository->saveCategory($_POST);
        $_SESSION['success_message'] = "Категорію успішно створено.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/service-categories');
    }

    #[Route('/service-categories/edit', name: 'admin_service_categories_edit', methods: ['GET'])]
    public function editServiceCategory() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $this->gate->authorize('admin.manage_service_categories');

        $id = (int)($_GET['id'] ?? 0);
        $category = $this->serviceRepository->findCategoryById($id);

        if (!$category) {
            return new \Symfony\Component\HttpFoundation\Response("Категорію послуг не знайдено", 404);
        }

        $response = $this->render('@Admin/service_categories/edit.html.twig', [
            'category' => $category,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
        return $response;
    }

    #[Route('/service-categories/edit', name: 'admin_service_categories_edit_post', methods: ['POST'])]
    public function updateServiceCategory() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $this->gate->authorize('admin.manage_service_categories');

        $id = (int)($_GET['id'] ?? 0);
        $category = $this->serviceRepository->findCategoryById($id);

        if (!$category) {
            return new \Symfony\Component\HttpFoundation\Response("Категорію послуг не знайдено", 404);
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:service_categories,name,' . $id],
            'description' => [], // Optional
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/service-categories/edit?id=' . $id);
        }

        $this->serviceRepository->updateCategory($id, $_POST);
        $_SESSION['success_message'] = "Категорію успішно оновлено.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/service-categories');
    }

    #[Route('/service-categories/delete', name: 'admin_service_categories_delete', methods: ['POST'])]
    public function deleteServiceCategory() : \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        $this->gate->authorize('admin.manage_service_categories');

        $id = (int)($_POST['id'] ?? 0);
        if ($this->serviceRepository->categoryHasServices($id)) {
            $_SESSION['error_message'] = "Не можна видалити категорію, до якої прив'язані послуги.";
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/service-categories');
        }
        $this->serviceRepository->deleteCategory($id);
        $_SESSION['success_message'] = "Категорію послуг успішно видалено.";
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/service-categories');
    }

    private function authorizeAdmin() : void
    {
        $this->checkAuth();
        $this->gate->authorize('system.manage');
    }
}

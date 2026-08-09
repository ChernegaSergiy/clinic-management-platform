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
use App\Core\Repository\SettingsRepository;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends \App\Core\Controller\AbstractController
{
    private RoleRepositoryInterface $roleRepository;
    private SettingsRepository $settingsRepository;
    private \App\Core\Validation\Validator $validator;

    public function __construct(
        RoleRepositoryInterface $roleRepository,
        SettingsRepository $settingsRepository,
        \App\Core\Validation\Validator $validator
    ) {
        $this->roleRepository = $roleRepository;
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

    private function authorizeAdmin() : void
    {
        $this->checkAuth();
        $this->gate->authorize('system.manage');
    }
}

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

namespace App\Http\User;

use App\Domain\Admin\AuthConfigRepository;
use App\Domain\User\MfaService;
use App\Domain\User\RoleRepository;
use App\Domain\User\UserRepository;
use App\Event\UserLoggedInEvent;
use App\Event\UserLoggedOutEvent;
use App\Shared\Validation\Validator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    private UserRepository $userRepository;
    private AuthConfigRepository $authConfigRepository;
    private RoleRepository $roleRepository;
    private MfaService $mfaService;
    private OAuthController $oauthController;
    private \App\Shared\Repository\SettingsRepository $settingsRepository;
    private Validator $validator;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        UserRepository $userRepository,
        AuthConfigRepository $authConfigRepository,
        RoleRepository $roleRepository,
        MfaService $mfaService,
        \App\Shared\Repository\SettingsRepository $settingsRepository,
        OAuthController $oauthController,
        Validator $validator,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->userRepository = $userRepository;
        $this->authConfigRepository = $authConfigRepository;
        $this->roleRepository = $roleRepository;
        $this->mfaService = $mfaService;
        $this->settingsRepository = $settingsRepository;
        $this->oauthController = $oauthController;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
    }

    #[Route('/login', name: 'login_form', methods: ['GET'])]
    public function showLoginForm() : Response
    {
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        return $this->render('user/login.html.twig', [
            'old' => $old,
            'errors' => [
                'login' => $errors['login'] ?? null,
                'email' => $errors['email'] ?? null,
                'password' => $errors['password'] ?? null,
            ],
            'authConfigs' => $this->authConfigRepository->findActive(),
        ]);
    }

    #[Route('/login', name: 'login_post', methods: ['POST'])]
    public function login() : Response
    {
        // Admin creation should be done via CLI commands or fixtures in Symfony

        $validator = $this->validator;
        $validator->validate($_POST, [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            return $this->redirectToRoute('login_form');
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        $user = $this->userRepository->findByEmail($email);
        $role = $user ? $this->roleRepository->findById((int)$user['role_id']) : null;

        if ($user && password_verify($password, $user['password_hash'])) {
            $mfaService = $this->mfaService;
            $mfaPolicy = $this->settingsRepository->getMfaPolicy();
            $mfaForceRoles = $this->settingsRepository->getMfaForceRoles();

            if ('disabled' === $mfaPolicy) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'role_id' => $user['role_id'],
                    'role_name' => $role['name'] ?? null,
                ];
                $this->eventDispatcher->dispatch(new UserLoggedInEvent($user['id'], $user['email']));
                $redirect = $_SESSION['intended_url'] ?? null;
                unset($_SESSION['intended_url']);
                if ($redirect) {
                    return new RedirectResponse($redirect);
                }

                return $this->redirectToRoute('dashboard');
            }

            $roleRequiresMfa = in_array((int)$user['role_id'], $mfaForceRoles, true);

            if ($roleRequiresMfa && !$mfaService->isMfaEnabled($user['id'])) {
                $_SESSION['mfa_required'] = true;
                $_SESSION['mfa_required_type'] = 'totp';
                $_SESSION['mfa_pending_user_id'] = $user['id'];
                $_SESSION['intended_url'] = $_SESSION['intended_url'] ?? null;
                unset($_SESSION['intended_url']);
                return $this->redirectToRoute('mfa_required_choice');
            }

            if ($mfaService->isMfaEnabled($user['id'])) {
                $_SESSION['mfa_pending_user_id'] = $user['id'];
                $_SESSION['mfa_type'] = $mfaService->getUserMfaStatus($user['id'])['type'];
                $_SESSION['intended_url'] = $_SESSION['intended_url'] ?? null;
                unset($_SESSION['intended_url']);
                return $this->redirectToRoute('mfa_verify');
            }

            $_SESSION['user'] = [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role_id' => $user['role_id'],
                'role_name' => $role['name'] ?? null,
            ];
            $this->eventDispatcher->dispatch(new UserLoggedInEvent($user['id'], $user['email']));
            $redirect = $_SESSION['intended_url'] ?? null;
            unset($_SESSION['intended_url']);
            if ($redirect) {
                return new RedirectResponse($redirect);
            }

            return $this->redirectToRoute('dashboard');
        } else {
            $_SESSION['errors'] = ['login' => 'Невірний email або пароль.'];
            $_SESSION['old'] = $_POST;
            return $this->redirectToRoute('login_form');
        }
    }

    /**
     * Redirects to the specified OAuth provider for authentication.
     *
     * @param  string   $provider
     * @return Response
     */
    #[Route('/oauth/redirect/{provider}', name: 'oauth_login', methods: ['GET'])]
    public function redirectToProvider(string $provider) : Response
    {
        return $this->oauthController->redirectToProvider($provider);
    }

    #[Route('/logout', name: 'logout', methods: ['GET', 'POST'])]
    public function logout() : Response
    {
        $userId = $_SESSION['user']['id'] ?? null;
        $userEmail = $_SESSION['user']['email'] ?? null;
        if ($userId && $userEmail) {
            $this->eventDispatcher->dispatch(new UserLoggedOutEvent($userId, $userEmail));
        }
        session_destroy();
        return $this->redirectToRoute('dashboard_redirect');
    }

    #[Route('/dashboard-redirect', name: 'dashboard_redirect', methods: ['GET'])]
    public function dashboard() : Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->redirectToRoute('dashboard');
    }
}

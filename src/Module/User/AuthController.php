<?php

namespace App\Module\User;

use App\Core\Event\EventDispatcherService;
use App\Database\Database;
use App\Event\UserLoggedInEvent;
use App\Event\UserLoggedOutEvent;
use App\Module\Admin\Repository\AuthConfigRepository;
use App\Module\User\Repository\RoleRepositoryInterface;
use App\Module\User\Repository\UserRepositoryInterface;
use App\Module\User\OAuthController;
use App\Core\Validation\Validator;

class AuthController extends \App\Core\Controller\AbstractController
{
    private UserRepositoryInterface $userRepository;
    private AuthConfigRepository $authConfigRepository;
    private RoleRepositoryInterface $roleRepository;
    private MfaService $mfaService;
    private OAuthController $oauthController;
    private \App\Core\Repository\SettingsRepository $settingsRepository;
    private Validator $validator;

    public function __construct(
        UserRepositoryInterface $userRepository,
        AuthConfigRepository $authConfigRepository,
        RoleRepositoryInterface $roleRepository,
        MfaService $mfaService,
        \App\Core\Repository\SettingsRepository $settingsRepository,
        OAuthController $oauthController,
        Validator $validator
    ) {
        $this->userRepository = $userRepository;
        $this->authConfigRepository = $authConfigRepository;
        $this->roleRepository = $roleRepository;
        $this->mfaService = $mfaService;
        $this->settingsRepository = $settingsRepository;
        $this->oauthController = $oauthController;
        $this->validator = $validator;
    }

    public function showLoginForm(): void
    {
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        $this->render('@modules/User/templates/login.html.twig', [
            'old' => $old,
            'errors' => $errors,
            'authConfigs' => $this->authConfigRepository->findActive(),
        ]);
    }

    public function login(): void
    {
        // Ensure at least one admin exists (useful for fresh installs without seeding)
        $this->userRepository->ensureDefaultAdminExists();

        $validator = $this->validator;
        $validator->validate($_POST, [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /login');
            exit();
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        $user = $this->userRepository->findByEmail($email);
        $role = $user ? $this->roleRepository->findById((int)$user['role_id']) : null;

        if ($user && password_verify($password, $user['password_hash'])) {
            $mfaService = $this->mfaService;
            $mfaPolicy = $this->settingsRepository->getMfaPolicy();
            $mfaForceRoles = $this->settingsRepository->getMfaForceRoles();

            if ($mfaPolicy === 'disabled') {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'role_id' => $user['role_id'],
                    'role_name' => $role['name'] ?? null,
                ];
                EventDispatcherService::getDispatcher()->dispatch(new UserLoggedInEvent($user['id'], $user['email']));
                $redirect = $_SESSION['intended_url'] ?? '/dashboard';
                unset($_SESSION['intended_url']);
                header('Location: ' . $redirect);
                exit();
            }

            $roleRequiresMfa = in_array((int)$user['role_id'], $mfaForceRoles, true);

            if ($roleRequiresMfa && !$mfaService->isMfaEnabled($user['id'])) {
                $_SESSION['mfa_required'] = true;
                $_SESSION['mfa_required_type'] = 'totp';
                $_SESSION['mfa_pending_user_id'] = $user['id'];
                $_SESSION['intended_url'] = $_SESSION['intended_url'] ?? '/dashboard';
                unset($_SESSION['intended_url']);
                header('Location: /user/mfa/required');
                exit();
            }

            if ($mfaService->isMfaEnabled($user['id'])) {
                $_SESSION['mfa_pending_user_id'] = $user['id'];
                $_SESSION['mfa_type'] = $mfaService->getUserMfaStatus($user['id'])['type'];
                $_SESSION['intended_url'] = $_SESSION['intended_url'] ?? '/dashboard';
                unset($_SESSION['intended_url']);
                header('Location: /user/mfa/verify');
                exit();
            }

            $_SESSION['user'] = [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role_id' => $user['role_id'],
                'role_name' => $role['name'] ?? null,
            ];
            EventDispatcherService::getDispatcher()->dispatch(new UserLoggedInEvent($user['id'], $user['email']));
            $redirect = $_SESSION['intended_url'] ?? '/dashboard';
            unset($_SESSION['intended_url']);
            header('Location: ' . $redirect);
            exit();
        } else {
            $_SESSION['errors'] = ['login' => 'Невірний email або пароль.'];
            $_SESSION['old'] = $_POST;
            header('Location: /login');
            exit();
        }
    }

    /**
     * Redirects to the specified OAuth provider for authentication.
     *
     * @param string $provider
     * @return void
     */
    public function redirectToProvider(string $provider): void
    {
        $this->oauthController->redirect($provider);
    }

    public function logout(): void
    {
        $userId = $_SESSION['user']['id'] ?? null;
        $userEmail = $_SESSION['user']['email'] ?? null;
        if ($userId && $userEmail) {
            EventDispatcherService::getDispatcher()->dispatch(new UserLoggedOutEvent($userId, $userEmail));
        }
        session_destroy();
        header('Location: /');
        exit();
    }

    public function dashboard(): void
    {
        $this->checkAuth();
        header('Location: /dashboard');
        exit();
    }
}

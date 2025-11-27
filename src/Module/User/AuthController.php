<?php

namespace App\Module\User;

use App\Core\View;
use App\Core\Validator;
use App\Module\User\Repository\UserRepository;
use App\Core\AuthGuard;
use App\Module\Admin\Repository\AuthConfigRepository;
use App\Module\User\Repository\RoleRepository;

class AuthController
{
    private UserRepository $userRepository;
    private AuthConfigRepository $authConfigRepository;
    private RoleRepository $roleRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->authConfigRepository = new AuthConfigRepository();
        $this->roleRepository = new RoleRepository();
    }

    public function showLoginForm(): void
    {
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('@modules/User/templates/login.html.twig', [
            'old' => $old,
            'errors' => $errors,
            'authConfigs' => $this->authConfigRepository->findActive(),
        ]);
    }

    public function login(): void
    {
        // Ensure at least one admin exists (useful for fresh installs without seeding)
        $this->userRepository->ensureDefaultAdminExists();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

        if ($user && password_verify($password, $user['password_hash'])) { // Corrected column name
            $_SESSION['user'] = [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role_id' => $user['role_id'],
                'role_name' => $role['name'] ?? null,
            ];
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
        (new OAuthController())->redirect($provider);
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: /');
        exit();
    }

    public function dashboard(): void
    {
        AuthGuard::check();
        header('Location: /dashboard');
        exit();
    }
}

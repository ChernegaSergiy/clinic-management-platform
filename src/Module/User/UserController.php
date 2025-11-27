<?php

namespace App\Module\User;

use App\Core\AuthGuard;
use App\Core\View;
use App\Module\User\Repository\UserRepository;
use App\Module\Admin\Repository\AuthConfigRepository;
use App\Module\User\Repository\UserOAuthIdentityRepository;

class UserController
{
    private UserRepository $userRepository;
    private AuthConfigRepository $authConfigRepository;
    private UserOAuthIdentityRepository $userOAuthIdentityRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->authConfigRepository = new AuthConfigRepository();
        $this->userOAuthIdentityRepository = new UserOAuthIdentityRepository();
    }

    public function profile(): void
    {
        AuthGuard::check(); // Ensure user is logged in

        $user = $this->userRepository->findById($_SESSION['user']['id']);

        if (!$user) {
            // This should not happen if the user is logged in
            session_destroy();
            header('Location: /login');
            exit();
        }

        $successMessage = $_SESSION['success_message'] ?? null;
        unset($_SESSION['success_message']);

        $linkedProviders = $this->userOAuthIdentityRepository->findAllByUserId($user['id']);

        View::render('@modules/User/templates/profile.html.twig', [
            'user' => $user,
            'successMessage' => $successMessage,
            'authConfigs' => $this->authConfigRepository->findActive(),
            'linkedProviders' => array_column($linkedProviders, 'provider'),
        ]);
    }

    public function unlinkProvider(string $provider): void
    {
        AuthGuard::check();

        $userId = $_SESSION['user']['id'];
        $this->userOAuthIdentityRepository->deleteByUserIdAndProvider($userId, $provider);

        $_SESSION['success_message'] = sprintf('Ваш акаунт %s було успішно відв\'язано.', ucfirst($provider));
        header('Location: /user/profile');
        exit();
    }
}

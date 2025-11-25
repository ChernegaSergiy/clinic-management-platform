<?php

namespace App\Module\User;

use App\Core\AuthGuard;
use App\Core\View;
use App\Module\User\Repository\UserRepository;

class UserController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
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

        View::render('@modules/User/templates/profile.html.twig', [
            'user' => $user,
            'successMessage' => $successMessage
        ]);
    }

    public function unlinkProvider(): void
    {
        AuthGuard::check();

        $this->userRepository->unlinkProvider($_SESSION['user']['id']);

        $_SESSION['success_message'] = 'Ваш акаунт було успішно відв\'язано.';
        header('Location: /user/profile');
        exit();
    }
}

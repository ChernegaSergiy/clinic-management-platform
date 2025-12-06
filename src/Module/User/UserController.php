<?php

namespace App\Module\User;

use App\Core\AuthGuard;
use App\Core\View;
use App\Module\User\Repository\UserRepository;
use App\Module\Admin\Repository\AuthConfigRepository;
use App\Module\User\Repository\UserOAuthIdentityRepository;
use App\Module\HRM\Repository\HrmRepository;

class UserController
{
    private UserRepository $userRepository;
    private AuthConfigRepository $authConfigRepository;
    private UserOAuthIdentityRepository $userOAuthIdentityRepository;
    private HrmRepository $hrmRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->authConfigRepository = new AuthConfigRepository();
        $this->userOAuthIdentityRepository = new UserOAuthIdentityRepository();
        $this->hrmRepository = new HrmRepository();
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

        $employee = $this->hrmRepository->findByUserId($user['id']);

        $successMessage = $_SESSION['success_message'] ?? null;
        unset($_SESSION['success_message']);

        $linkedProviders = $this->userOAuthIdentityRepository->findAllByUserId($user['id']);

        View::render('@modules/User/templates/profile.html.twig', [
            'user' => $user,
            'employee' => $employee,
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

    public function uploadPhoto(): void
    {
        AuthGuard::check();

        $userId = $_SESSION['user']['id'];
        $uploadDir = __DIR__ . '/../../../public/uploads/avatars/'; // www/public/uploads/avatars/

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (empty($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error_message'] = 'Будь ласка, виберіть файл для завантаження.';
            header('Location: /user/profile');
            exit();
        }

        $file = $_FILES['profile_photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error_message'] = 'Дозволені лише файли зображень (JPG, PNG, GIF).';
            header('Location: /user/profile');
            exit();
        }

        if ($file['size'] > $maxFileSize) {
            $_SESSION['error_message'] = 'Розмір файлу не повинен перевищувати 2MB.';
            header('Location: /user/profile');
            exit();
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('avatar_') . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        $relativePath = '/uploads/avatars/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Update user's profile photo path in the database
            $this->userRepository->updateProfilePhotoPath($userId, $relativePath);
            $_SESSION['success_message'] = 'Фото профілю успішно завантажено.';
        } else {
            $_SESSION['error_message'] = 'Не вдалося завантажити файл. Спробуйте ще раз.';
        }

        header('Location: /user/profile');
        exit();
    }
}

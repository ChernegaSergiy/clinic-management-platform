<?php

namespace App\Bundles\UserBundle\Controller;

use App\Bundles\UserBundle\Repository\UserOAuthIdentityRepository;
use App\Bundles\UserBundle\Repository\UserRepositoryInterface;
use App\Core\Repository\SettingsRepository;
use App\Module\Admin\Repository\AuthConfigRepository;
use App\Module\Hrm\Repository\HrmRepositoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends \App\Core\Controller\AbstractController
{
    private UserRepositoryInterface $userRepository;
    private AuthConfigRepository $authConfigRepository;
    private UserOAuthIdentityRepository $userOAuthIdentityRepository;
    private HrmRepositoryInterface $hrmRepository;
    private SettingsRepository $settingsRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        AuthConfigRepository $authConfigRepository,
        UserOAuthIdentityRepository $userOAuthIdentityRepository,
        HrmRepositoryInterface $hrmRepository,
        SettingsRepository $settingsRepository
    ) {
        $this->userRepository = $userRepository;
        $this->authConfigRepository = $authConfigRepository;
        $this->userOAuthIdentityRepository = $userOAuthIdentityRepository;
        $this->hrmRepository = $hrmRepository;
        $this->settingsRepository = $settingsRepository;
    }

    #[Route('/user/profile', name: 'user_profile', methods: ['GET'])]
    public function profile() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth(); // Ensure user is logged in

        $user = $this->userRepository->findById($_SESSION['user']['id']);

        if (!$user) {
            // This should not happen if user is logged in
            session_destroy();
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/login');
        }

        $employee = $this->hrmRepository->findByUserId($user['id']);

        $successMessage = $_SESSION['success_message'] ?? null;
        unset($_SESSION['success_message']);

        $linkedProviders = $this->userOAuthIdentityRepository->findAllByUserId($user['id']);

        $mfaPolicy = $this->settingsRepository->getMfaPolicy();

        return $this->render('@User/profile.html.twig', [
            'user' => $user,
            'employee' => $employee,
            'successMessage' => $successMessage,
            'authConfigs' => $this->authConfigRepository->findActive(),
            'linkedProviders' => array_column($linkedProviders, 'provider'),
            'mfaPolicy' => $mfaPolicy,
        ]);
    }

    #[Route('/user/profile/unlink-provider/{provider}', name: 'user_profile_unlink', methods: ['POST'])]
    public function unlinkProvider(string $provider) : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();

        $userId = $_SESSION['user']['id'];
        $this->userOAuthIdentityRepository->deleteByUserIdAndProvider($userId, $provider);

        $_SESSION['success_message'] = sprintf('Ваш акаунт %s було успішно відв\'язано.', ucfirst($provider));
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
    }

    #[Route('/user/profile/upload-photo', name: 'user_profile_photo', methods: ['POST'])]
    public function uploadPhoto() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();

        $userId = $_SESSION['user']['id'];
        $uploadDir = __DIR__ . '/../../../public/uploads/avatars/';

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (empty($_FILES['profile_photo']) || UPLOAD_ERR_OK !== $_FILES['profile_photo']['error']) {
            $_SESSION['error_message'] = 'Будь ласка, виберіть файл для завантаження.';
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
        }

        $file = $_FILES['profile_photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error_message'] = 'Дозволені лише файли зображень (JPG, PNG, GIF).';
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
        }

        if ($file['size'] > $maxFileSize) {
            $_SESSION['error_message'] = 'Розмір файлу не повинен перевищувати 2MB.';
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
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

        return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
    }

    #[Route('/user/clear-messages', name: 'user_messages_clear', methods: ['POST'])]
    public function clearMessages() : \Symfony\Component\HttpFoundation\Response
    {
        unset($_SESSION['success_message'], $_SESSION['error_message']);
        return new \Symfony\Component\HttpFoundation\Response('', 200);
    }
}

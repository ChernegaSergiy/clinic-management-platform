<?php

namespace App\Module\User;

use App\Core\Auth\AuthGuard;
use App\Core\Http\View;
use App\Module\User\Repository\UserRepository;

class MfaController
{
    private MfaService $mfaService;
    private \App\Module\User\Repository\UserRepository $userRepository;

    public function __construct()
    {
        $this->mfaService = new MfaService();
        $this->userRepository = new UserRepository();
    }

    public function showMfaSetup(): void
    {
        AuthGuard::check();

        $userId = $_SESSION['user']['id'];
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            session_destroy();
            header('Location: /login');
            exit();
        }

        if ($this->mfaService->isMfaEnabled($userId)) {
            header('Location: /user/profile');
            exit();
        }

        $secret = $this->mfaService->generateSecret();
        $qrCode = $this->mfaService->generateQRCode($secret, $user['email']);
        $backupCodes = $this->mfaService->generateBackupCodes();

        $_SESSION['mfa_setup_secret'] = $secret;
        $_SESSION['mfa_setup_backup_codes'] = $backupCodes;

        View::render('@modules/User/templates/mfa_setup.html.twig', [
            'user' => $user,
            'secret' => $secret,
            'qrCode' => $qrCode,
            'backupCodes' => $backupCodes,
        ]);
    }

    public function verifyMfaSetup(): void
    {
        AuthGuard::check();

        $userId = $_SESSION['user']['id'];
        $secret = $_SESSION['mfa_setup_secret'] ?? null;
        $backupCodes = $_SESSION['mfa_setup_backup_codes'] ?? [];

        if (!$secret) {
            header('Location: /user/mfa/setup');
            exit();
        }

        $code = $_POST['code'] ?? '';

        if (empty($code)) {
            $_SESSION['error_message'] = 'Будь ласка, введіть код з додатку.';
            header('Location: /user/mfa/setup');
            exit();
        }

        if ($this->mfaService->verifyCode($secret, $code)) {
            $this->mfaService->enableMfaForUser($userId, $secret, $backupCodes);

            unset($_SESSION['mfa_setup_secret'], $_SESSION['mfa_setup_backup_codes']);

            $_SESSION['success_message'] = 'Двофакторну автентифікацію успішно увімкнено!';
            header('Location: /user/profile');
            exit();
        } else {
            $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
            header('Location: /user/mfa/setup');
            exit();
        }
    }

    public function disableMfa(): void
    {
        AuthGuard::check();

        $userId = $_SESSION['user']['id'];
        $password = $_POST['password'] ?? '';

        $user = $this->userRepository->findById($userId);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['error_message'] = 'Невірний пароль.';
            header('Location: /user/profile');
            exit();
        }

        $this->mfaService->disableMfaForUser($userId);

        $_SESSION['success_message'] = 'Двофакторну автентифікацію вимкнено.';
        header('Location: /user/profile');
        exit();
    }

    public function showMfaVerify(): void
    {
        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            header('Location: /login');
            exit();
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            session_destroy();
            header('Location: /login');
            exit();
        }

        $errorMessage = $_SESSION['mfa_error'] ?? null;
        unset($_SESSION['mfa_error']);

        View::render('@modules/User/templates/mfa_verify.html.twig', [
            'user' => $user,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function verifyMfa(): void
    {
        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            header('Location: /login');
            exit();
        }

        $code = $_POST['code'] ?? '';

        if (empty($code)) {
            $_SESSION['mfa_error'] = 'Будь ласка, введіть код.';
            header('Location: /user/mfa/verify');
            exit();
        }

        if ($this->mfaService->verifyUserMfa($userId, $code)) {
            unset($_SESSION['mfa_pending_user_id']);

            $user = $this->userRepository->findById($userId);
            $roleRepository = new \App\Module\User\Repository\RoleRepository();
            $role = $roleRepository->findById((int)$user['role_id']);

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
            $_SESSION['mfa_error'] = 'Невірний код. Спробуйте ще раз.';
            header('Location: /user/mfa/verify');
            exit();
        }
    }

    public function regenerateBackupCodes(): void
    {
        AuthGuard::check();

        $userId = $_SESSION['user']['id'];
        $password = $_POST['password'] ?? '';

        $user = $this->userRepository->findById($userId);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['error_message'] = 'Невірний пароль.';
            header('Location: /user/profile');
            exit();
        }

        if (!$this->mfaService->isMfaEnabled($userId)) {
            header('Location: /user/profile');
            exit();
        }

        $backupCodes = $this->mfaService->generateBackupCodes();

        $stmt = \App\Database\Database::getInstance()->prepare("
            UPDATE users SET mfa_backup_codes = :codes WHERE id = :id
        ");
        $stmt->execute(['id' => $userId, 'codes' => json_encode($backupCodes)]);

        $_SESSION['success_message'] = 'Коди відновлення оновлено.';
        header('Location: /user/profile');
        exit();
    }
}

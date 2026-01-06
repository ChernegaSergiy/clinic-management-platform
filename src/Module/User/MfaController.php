<?php

namespace App\Module\User;

use App\Core\Auth\AuthGuard;
use App\Core\Auth\MfaGuard;
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

    public function showMfaSetup(string $type = 'totp'): void
    {
        if (!in_array($type, ['totp', 'hotp'], true)) {
            $type = 'totp';
        }

        if (isset($_SESSION['user'])) {
            AuthGuard::check();
        }

        $userId = $_SESSION['mfa_pending_user_id'] ?? $_SESSION['user']['id'] ?? null;

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

        $isReset = isset($_GET['reset']) && $_GET['reset'] === '1';

        if ($this->mfaService->isMfaEnabled($userId) && !$isReset) {
            header('Location: /user/profile');
            exit();
        }

        if ($type === 'hotp') {
            $secret = $_SESSION['hotp_setup_secret'] ?? $this->mfaService->generateHotpSecret();
            $counter = $_SESSION['hotp_setup_counter'] ?? 0;
            $qrCode = $this->mfaService->generateHotpQRCode($secret, $user['email'], $counter);
            $backupCodes = $_SESSION['hotp_setup_backup_codes'] ?? $this->mfaService->generateBackupCodes();

            $_SESSION['hotp_setup_secret'] = $secret;
            $_SESSION['hotp_setup_counter'] = $counter;
            $_SESSION['hotp_setup_backup_codes'] = $backupCodes;

            View::render('@modules/User/templates/hotp_setup.html.twig', [
                'user' => $user,
                'secret' => $secret,
                'qrCode' => $qrCode,
                'backupCodes' => $backupCodes,
                'counter' => $counter,
                'isReset' => $isReset,
            ]);
        } else {
            $secret = $this->mfaService->generateSecret();
            $qrCode = $this->mfaService->generateQRCode($secret, $user['email']);
            $backupCodes = $this->mfaService->generateBackupCodes();

            $_SESSION['mfa_setup_secret'] = $secret;
            $_SESSION['mfa_setup_backup_codes'] = $backupCodes;
            $_SESSION['mfa_setup_is_reset'] = $isReset;

            View::render('@modules/User/templates/mfa_setup.html.twig', [
                'user' => $user,
                'secret' => $secret,
                'qrCode' => $qrCode,
                'backupCodes' => $backupCodes,
                'isReset' => $isReset,
            ]);
        }
    }

    public function showMfaRequiredChoice(): void
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

        View::render('@modules/User/templates/mfa_required_choice.html.twig', [
            'user' => $user,
        ]);
    }

    public function showMfaRequired(string $type = 'totp'): void
    {
        if (!in_array($type, ['totp', 'hotp'], true)) {
            header('Location: /user/mfa/required');
            exit();
        }

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

        if ($type === 'hotp') {
            $secret = $_SESSION['hotp_setup_secret'] ?? null;
            $backupCodes = $_SESSION['hotp_setup_backup_codes'] ?? [];

            if (!$secret || !$backupCodes) {
                $secret = $this->mfaService->generateHotpSecret();
                $counter = 0;
                $qrCode = $this->mfaService->generateHotpQRCode($secret, $user['email'], $counter);
                $backupCodes = $this->mfaService->generateBackupCodes();

                $_SESSION['hotp_setup_secret'] = $secret;
                $_SESSION['hotp_setup_counter'] = $counter;
                $_SESSION['hotp_setup_backup_codes'] = $backupCodes;
            } else {
                $counter = $_SESSION['hotp_setup_counter'] ?? 0;
                $qrCode = $this->mfaService->generateHotpQRCode($secret, $user['email'], $counter);
            }

            View::render('@modules/User/templates/hotp_required.html.twig', [
                'user' => $user,
                'secret' => $secret,
                'qrCode' => $qrCode,
                'backupCodes' => $backupCodes,
                'counter' => $counter,
            ]);
        } else {
            $secret = $_SESSION['mfa_setup_secret'] ?? null;
            $backupCodes = $_SESSION['mfa_setup_backup_codes'] ?? [];

            if (!$secret || !$backupCodes) {
                $secret = $this->mfaService->generateSecret();
                $qrCode = $this->mfaService->generateQRCode($secret, $user['email']);
                $backupCodes = $this->mfaService->generateBackupCodes();

                $_SESSION['mfa_setup_secret'] = $secret;
                $_SESSION['mfa_setup_backup_codes'] = $backupCodes;
            } else {
                $qrCode = $this->mfaService->generateQRCode($secret, $user['email']);
            }

            View::render('@modules/User/templates/mfa_required.html.twig', [
                'user' => $user,
                'secret' => $secret,
                'qrCode' => $qrCode,
                'backupCodes' => $backupCodes,
            ]);
        }
    }

    public function verifyMfaSetup(string $type = 'totp'): void
    {
        if (!in_array($type, ['totp', 'hotp'], true)) {
            $type = 'totp';
        }

        if (isset($_SESSION['user'])) {
            AuthGuard::check();
        }

        $userId = $_SESSION['mfa_pending_user_id'] ?? $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            header('Location: /login');
            exit();
        }

        $isReset = isset($_GET['reset']) && $_GET['reset'] === '1';

        if ($type === 'hotp') {
            $secret = $_SESSION['hotp_setup_secret'] ?? null;
            $backupCodes = $_SESSION['hotp_setup_backup_codes'] ?? [];
            $counter = $_SESSION['hotp_setup_counter'] ?? 0;

            if (!$secret) {
                $redirectUrl = $isReset ? '/user/mfa/setup/hotp?reset=1' : '/user/mfa/setup/hotp';
                header('Location: ' . $redirectUrl);
                exit();
            }

            $code = $_POST['code'] ?? '';

            if (empty($code)) {
                $_SESSION['error_message'] = 'Будь ласка, введіть код.';
                $redirectUrl = $isReset ? '/user/mfa/setup/hotp?reset=1' : '/user/mfa/setup/hotp';
                header('Location: ' . $redirectUrl);
                exit();
            }

            if ($this->mfaService->verifyHotpCode($secret, $code, $counter)) {
                $this->mfaService->enableHotpForUser($userId, $secret, $backupCodes, $counter);

                unset($_SESSION['hotp_setup_secret'], $_SESSION['hotp_setup_backup_codes'], $_SESSION['hotp_setup_counter']);

                if (isset($_SESSION['user'])) {
                    $_SESSION['success_message'] = 'Двофакторну автентифікацію HOTP успішно увімкнено!';
                    header('Location: /user/profile');
                } else {
                    MfaGuard::clearRequired();
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
                }
                exit();
            } else {
                $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
                $redirectUrl = $isReset ? '/user/mfa/setup/hotp?reset=1' : '/user/mfa/setup/hotp';
                header('Location: ' . $redirectUrl);
                exit();
            }
        } else {
            $secret = $_SESSION['mfa_setup_secret'] ?? null;
            $backupCodes = $_SESSION['mfa_setup_backup_codes'] ?? [];

            if (!$secret) {
                $redirectUrl = $isReset ? '/user/mfa/setup/totp?reset=1' : '/user/mfa/setup/totp';
                header('Location: ' . $redirectUrl);
                exit();
            }

            $code = $_POST['code'] ?? '';

            if (empty($code)) {
                $_SESSION['error_message'] = 'Будь ласка, введіть код з додатку.';
                $redirectUrl = $isReset ? '/user/mfa/setup/totp?reset=1' : '/user/mfa/setup/totp';
                header('Location: ' . $redirectUrl);
                exit();
            }

            if ($this->mfaService->verifyCode($secret, $code)) {
                $this->mfaService->enableMfaForUser($userId, $secret, $backupCodes, 'totp');

                unset($_SESSION['mfa_setup_secret'], $_SESSION['mfa_setup_backup_codes']);

                if (isset($_SESSION['user'])) {
                    $_SESSION['success_message'] = 'Двофакторну автентифікацію успішно увімкнено!';
                    header('Location: /user/profile');
                } else {
                    MfaGuard::clearRequired();
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
                }
                exit();
            } else {
                $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
                $redirectUrl = $isReset ? '/user/mfa/setup/totp?reset=1' : '/user/mfa/setup/totp';
                header('Location: ' . $redirectUrl);
                exit();
            }
        }
    }

    public function verifyMfaRequired(string $type = 'totp'): void
    {
        if (!in_array($type, ['totp', 'hotp'], true)) {
            header('Location: /user/mfa/required');
            exit();
        }

        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            header('Location: /login');
            exit();
        }

        if ($type === 'hotp') {
            $secret = $_SESSION['hotp_setup_secret'] ?? null;
            $backupCodes = $_SESSION['hotp_setup_backup_codes'] ?? [];
            $counter = $_SESSION['hotp_setup_counter'] ?? 0;

            if (!$secret) {
                $_SESSION['error_message'] = 'Помилка генерування коду. Спробуйте ще раз.';
                header('Location: /user/mfa/required/hotp');
                exit();
            }

            $code = $_POST['code'] ?? '';

            if (empty($code)) {
                $_SESSION['error_message'] = 'Будь ласка, введіть код.';
                header('Location: /user/mfa/required/hotp');
                exit();
            }

            if ($this->mfaService->verifyHotpCode($secret, $code, $counter)) {
                $this->mfaService->enableHotpForUser($userId, $secret, $backupCodes, $counter);

                unset($_SESSION['hotp_setup_secret'], $_SESSION['hotp_setup_backup_codes'], $_SESSION['hotp_setup_counter']);
                MfaGuard::clearRequired();

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
                $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
                header('Location: /user/mfa/required/hotp');
                exit();
            }
        } else {
            $secret = $_SESSION['mfa_setup_secret'] ?? null;
            $backupCodes = $_SESSION['mfa_setup_backup_codes'] ?? [];

            if (!$secret) {
                $_SESSION['error_message'] = 'Помилка генерування коду. Спробуйте ще раз.';
                header('Location: /user/mfa/required/totp');
                exit();
            }

            $code = $_POST['code'] ?? '';

            if (empty($code)) {
                $_SESSION['error_message'] = 'Будь ласка, введіть код з додатку.';
                header('Location: /user/mfa/required/totp');
                exit();
            }

            if ($this->mfaService->verifyCode($secret, $code)) {
                $this->mfaService->enableMfaForUser($userId, $secret, $backupCodes, 'totp');

                unset($_SESSION['mfa_setup_secret'], $_SESSION['mfa_setup_backup_codes']);
                MfaGuard::clearRequired();

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
                $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
                header('Location: /user/mfa/required/totp');
                exit();
            }
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

        $mfaType = $_SESSION['mfa_type'] ?? $this->mfaService->getUserMfaStatus($userId)['type'] ?? 'totp';

        View::render('@modules/User/templates/mfa_verify.html.twig', [
            'user' => $user,
            'errorMessage' => $errorMessage,
            'mfaType' => $mfaType,
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
            MfaGuard::clearRequired();

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

        $_SESSION['new_backup_codes'] = $backupCodes;
        header('Location: /user/profile');
        exit();
    }

    public function clearNewBackupCodes(): void
    {
        unset($_SESSION['new_backup_codes']);
        header('Location: /user/profile');
        exit();
    }
}

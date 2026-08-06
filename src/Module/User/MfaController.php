<?php

namespace App\Module\User;

use App\Core\Auth\MfaGuard;
use App\Core\Repository\SettingsRepository;
use App\Module\User\Repository\RoleRepositoryInterface;
use App\Module\User\Repository\UserRepositoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class MfaController extends \App\Core\Controller\AbstractController
{
    private MfaService $mfaService;
    private UserRepositoryInterface $userRepository;
    private SettingsRepository $settingsRepository;
    private RoleRepositoryInterface $roleRepository;
    private \Doctrine\Persistence\ManagerRegistry $registry;

    public function __construct(
        MfaService $mfaService,
        UserRepositoryInterface $userRepository,
        SettingsRepository $settingsRepository,
        RoleRepositoryInterface $roleRepository,
        \Doctrine\Persistence\ManagerRegistry $registry
    ) {
        $this->mfaService = $mfaService;
        $this->userRepository = $userRepository;
        $this->settingsRepository = $settingsRepository;
        $this->roleRepository = $roleRepository;
        $this->registry = $registry;
    }

    private function prepareHotpSetup(int $userId, array &$secret, array &$backupCodes, int &$counter, string &$qrCode) : void
    {
        $secret = $_SESSION['hotp_setup_secret'] ?? $this->mfaService->generateHotpSecret();
        $counter = $_SESSION['hotp_setup_counter'] ?? 0;
        $backupCodes = $_SESSION['hotp_setup_backup_codes'] ?? $this->mfaService->generateBackupCodes();

        $user = $this->userRepository->findById($userId);
        $qrCode = $this->mfaService->generateHotpQRCode($secret, $user['email'], $counter);

        $_SESSION['hotp_setup_secret'] = $secret;
        $_SESSION['hotp_setup_counter'] = $counter;
        $_SESSION['hotp_setup_backup_codes'] = $backupCodes;
    }

    private function prepareTotpSetup(int $userId, string &$secret, array &$backupCodes, string &$qrCode) : void
    {
        $secret = $this->mfaService->generateSecret();
        $user = $this->userRepository->findById($userId);
        $qrCode = $this->mfaService->generateQRCode($secret, $user['email']);
        $backupCodes = $this->mfaService->generateBackupCodes();

        $_SESSION['mfa_setup_secret'] = $secret;
        $_SESSION['mfa_setup_backup_codes'] = $backupCodes;
    }

    #[Route('/user/mfa/setup/{type}', name: 'mfa_setup', methods: ['GET'], defaults: ['type' => 'totp'])]
    public function showMfaSetup(string $type = 'totp') : \Symfony\Component\HttpFoundation\Response
    {
        if (!in_array($type, ['totp', 'hotp'], true)) {
            $type = 'totp';
        }

        if (isset($_SESSION['user'])) {
            $this->checkAuth();

            $mfaPolicy = $this->settingsRepository->getMfaPolicy();

            if ('disabled' === $mfaPolicy) {
                $_SESSION['error_message'] = 'Двофакторна автентифікація вимкнена в налаштуваннях системи.';
                return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
            }
        }

        $userId = $_SESSION['mfa_pending_user_id'] ?? $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/login');
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            session_destroy();
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/login');
        }

        $isReset = isset($_GET['reset']) && '1' === $_GET['reset'];

        if ($this->mfaService->isMfaEnabled($userId) && !$isReset) {
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
        }

        if ('hotp' === $type) {
            $secret = [];
            $backupCodes = [];
            $counter = 0;
            $qrCode = '';
            $this->prepareHotpSetup($userId, $secret, $backupCodes, $counter, $qrCode);

            return $this->render('@modules/User/templates/hotp_setup.html.twig', [
                'user' => $user,
                'secret' => $secret,
                'qrCode' => $qrCode,
                'backupCodes' => $backupCodes,
                'counter' => $counter,
                'isReset' => $isReset,
            ]);
        } else {
            $secret = '';
            $backupCodes = [];
            $qrCode = '';
            $this->prepareTotpSetup($userId, $secret, $backupCodes, $qrCode);
            $_SESSION['mfa_setup_is_reset'] = $isReset;

            return $this->render('@modules/User/templates/mfa_setup.html.twig', [
                'user' => $user,
                'secret' => $secret,
                'qrCode' => $qrCode,
                'backupCodes' => $backupCodes,
                'isReset' => $isReset,
            ]);
        }
    }

    #[Route('/user/mfa/required_choice', name: 'mfa_required_choice', methods: ['GET'])]
    public function showMfaRequiredChoice() : \Symfony\Component\HttpFoundation\Response
    {
        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/login');
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            session_destroy();
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/login');
        }

        return $this->render('@modules/User/templates/mfa_required_choice.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/mfa/required/{type}', name: 'mfa_required', methods: ['GET'], defaults: ['type' => 'totp'])]
    public function showMfaRequired(string $type = 'totp') : \Symfony\Component\HttpFoundation\Response
    {
        if (!in_array($type, ['totp', 'hotp'], true)) {
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/mfa/required');
        }

        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/login');
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            session_destroy();
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/login');
        }

        if ('hotp' === $type) {
            $secret = $_SESSION['hotp_setup_secret'] ?? null;
            $backupCodes = $_SESSION['hotp_setup_backup_codes'] ?? [];

            if (!$secret || !$backupCodes) {
                $secret = [];
                $counter = 0;
                $qrCode = '';
                $this->prepareHotpSetup($userId, $secret, $backupCodes, $counter, $qrCode);
            } else {
                $counter = $_SESSION['hotp_setup_counter'] ?? 0;
                $qrCode = $this->mfaService->generateHotpQRCode($secret, $user['email'], $counter);
            }

            return $this->render('@modules/User/templates/hotp_required.html.twig', [
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
                $secret = '';
                $qrCode = '';
                $this->prepareTotpSetup($userId, $secret, $backupCodes, $qrCode);
            } else {
                $qrCode = $this->mfaService->generateQRCode($secret, $user['email']);
            }

            return $this->render('@modules/User/templates/totp_required.html.twig', [
                'user' => $user,
                'secret' => $secret,
                'qrCode' => $qrCode,
                'backupCodes' => $backupCodes,
            ]);
        }
    }

    #[Route('/user/mfa/setup/{type}', name: 'mfa_setup_verify', methods: ['POST'], defaults: ['type' => 'totp'])]
    public function verifyMfaSetup(string $type = 'totp') : \Symfony\Component\HttpFoundation\Response
    {
        if (!in_array($type, ['totp', 'hotp'], true)) {
            $type = 'totp';
        }

        if (isset($_SESSION['user'])) {
            $this->checkAuth();

            $mfaPolicy = $this->settingsRepository->getMfaPolicy();

            if ('disabled' === $mfaPolicy) {
                $_SESSION['error_message'] = 'Двофакторна автентифікація вимкнена в налаштуваннях системи.';
                return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
            }
        }

        $userId = $_SESSION['mfa_pending_user_id'] ?? $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/login');
        }

        $isReset = isset($_GET['reset']) && '1' === $_GET['reset'];

        if ('hotp' === $type) {
            $secret = $_SESSION['hotp_setup_secret'] ?? null;
            $backupCodes = $_SESSION['hotp_setup_backup_codes'] ?? [];
            $counter = $_SESSION['hotp_setup_counter'] ?? 0;

            if (!$secret) {
                $redirectUrl = $isReset ? '/user/mfa/setup/hotp?reset=1' : '/user/mfa/setup/hotp';
                return new \Symfony\Component\HttpFoundation\RedirectResponse($redirectUrl);
            }

            $code = $_POST['code'] ?? '';

            if (empty($code)) {
                $_SESSION['error_message'] = 'Будь ласка, введіть код.';
                $redirectUrl = $isReset ? '/user/mfa/setup/hotp?reset=1' : '/user/mfa/setup/hotp';
                return new \Symfony\Component\HttpFoundation\RedirectResponse($redirectUrl);
            }

            $lastCounter = $_SESSION['hotp_setup_last_counter'] ?? 0;
            $verifiedCounter = $this->mfaService->verifyHotpCodeWithCounter($secret, $code, $counter, $lastCounter);

            if (null !== $verifiedCounter) {
                $_SESSION['hotp_setup_last_counter'] = $verifiedCounter;
                $this->mfaService->enableHotpForUser($userId, $secret, $backupCodes, $verifiedCounter + 1);

                unset($_SESSION['hotp_setup_secret'], $_SESSION['hotp_setup_backup_codes'], $_SESSION['hotp_setup_counter'], $_SESSION['hotp_setup_last_counter']);

                if (isset($_SESSION['user'])) {
                    $_SESSION['success_message'] = 'Двофакторну автентифікацію HOTP успішно увімкнено!';
                    return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
                } else {
                    MfaGuard::clearRequired();
                    $user = $this->userRepository->findById($userId);
                    $role = $this->roleRepository->findById((int)$user['role_id']);

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

                    return new \Symfony\Component\HttpFoundation\RedirectResponse($redirect);
                }
            } else {
                $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
                $redirectUrl = $isReset ? '/user/mfa/setup/hotp?reset=1' : '/user/mfa/setup/hotp';
                return new \Symfony\Component\HttpFoundation\RedirectResponse($redirectUrl);
            }
        } else {
            $secret = $_SESSION['mfa_setup_secret'] ?? null;
            $backupCodes = $_SESSION['mfa_setup_backup_codes'] ?? [];

            if (!$secret) {
                $redirectUrl = $isReset ? '/user/mfa/setup/totp?reset=1' : '/user/mfa/setup/totp';
                return new \Symfony\Component\HttpFoundation\RedirectResponse($redirectUrl);
            }

            $code = $_POST['code'] ?? '';

            if (empty($code)) {
                $_SESSION['error_message'] = 'Будь ласка, введіть код з додатку.';
                $redirectUrl = $isReset ? '/user/mfa/setup/totp?reset=1' : '/user/mfa/setup/totp';
                return new \Symfony\Component\HttpFoundation\RedirectResponse($redirectUrl);
            }

            if ($this->mfaService->verifyCode($secret, $code)) {
                $this->mfaService->enableMfaForUser($userId, $secret, $backupCodes, 'totp');

                unset($_SESSION['mfa_setup_secret'], $_SESSION['mfa_setup_backup_codes']);

                if (isset($_SESSION['user'])) {
                    $_SESSION['success_message'] = 'Двофакторну автентифікацію успішно увімкнено!';
                    return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
                } else {
                    MfaGuard::clearRequired();
                    $user = $this->userRepository->findById($userId);
                    $role = $this->roleRepository->findById((int)$user['role_id']);

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

                    return new \Symfony\Component\HttpFoundation\RedirectResponse($redirect);
                }
            } else {
                $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
                $redirectUrl = $isReset ? '/user/mfa/setup/totp?reset=1' : '/user/mfa/setup/totp';
                return new \Symfony\Component\HttpFoundation\RedirectResponse($redirectUrl);
            }
        }
    }

    #[Route('/user/mfa/required/verify/{type}', name: 'mfa_required_verify', methods: ['POST'], defaults: ['type' => 'totp'])]
    public function verifyMfaRequired(string $type = 'totp') : \Symfony\Component\HttpFoundation\Response
    {
        if (!in_array($type, ['totp', 'hotp'], true)) {
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/mfa/required');
        }

        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/login');
        }

        if ('hotp' === $type) {
            $secret = $_SESSION['hotp_setup_secret'] ?? null;
            $backupCodes = $_SESSION['hotp_setup_backup_codes'] ?? [];
            $counter = $_SESSION['hotp_setup_counter'] ?? 0;

            if (!$secret) {
                $_SESSION['error_message'] = 'Помилка генерування коду. Спробуйте ще раз.';
                return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/mfa/required/hotp');
            }

            $code = $_POST['code'] ?? '';

            if (empty($code)) {
                $_SESSION['error_message'] = 'Будь ласка, введіть код.';
                return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/mfa/required/hotp');
            }

            $lastCounter = $_SESSION['hotp_setup_last_counter'] ?? 0;
            $verifiedCounter = $this->mfaService->verifyHotpCodeWithCounter($secret, $code, $counter, $lastCounter);

            if (null !== $verifiedCounter) {
                $_SESSION['hotp_setup_last_counter'] = $verifiedCounter;
                $this->mfaService->enableHotpForUser($userId, $secret, $backupCodes, $verifiedCounter + 1);

                unset($_SESSION['hotp_setup_secret'], $_SESSION['hotp_setup_backup_codes'], $_SESSION['hotp_setup_counter'], $_SESSION['hotp_setup_last_counter']);
                MfaGuard::clearRequired();

                $user = $this->userRepository->findById($userId);
                $role = $this->roleRepository->findById((int)$user['role_id']);

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

                return new \Symfony\Component\HttpFoundation\RedirectResponse($redirect);
            } else {
                $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
                return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/mfa/required/hotp');
            }
        } else {
            $secret = $_SESSION['mfa_setup_secret'] ?? null;
            $backupCodes = $_SESSION['mfa_setup_backup_codes'] ?? [];

            if (!$secret) {
                $_SESSION['error_message'] = 'Помилка генерування коду. Спробуйте ще раз.';
                return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/mfa/required/totp');
            }

            $code = $_POST['code'] ?? '';

            if (empty($code)) {
                $_SESSION['error_message'] = 'Будь ласка, введіть код з додатку.';
                return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/mfa/required/totp');
            }

            if ($this->mfaService->verifyCode($secret, $code)) {
                $this->mfaService->enableMfaForUser($userId, $secret, $backupCodes, 'totp');

                unset($_SESSION['mfa_setup_secret'], $_SESSION['mfa_setup_backup_codes']);
                MfaGuard::clearRequired();

                $user = $this->userRepository->findById($userId);
                $role = $this->roleRepository->findById((int)$user['role_id']);

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

                return new \Symfony\Component\HttpFoundation\RedirectResponse($redirect);
            } else {
                $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
                return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/mfa/required/totp');
            }
        }
    }

    #[Route('/user/mfa/disable', name: 'mfa_disable', methods: ['POST'])]
    public function disableMfa() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();

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
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
    }

    #[Route('/user/mfa/verify', name: 'mfa_verify', methods: ['GET'])]
    public function showMfaVerify() : \Symfony\Component\HttpFoundation\Response
    {
        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/login');
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            session_destroy();
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/login');
        }

        $errorMessage = $_SESSION['mfa_error'] ?? null;
        unset($_SESSION['mfa_error']);

        $mfaType = $_SESSION['mfa_type'] ?? $this->mfaService->getUserMfaStatus($userId)['type'] ?? 'totp';

        return $this->render('@modules/User/templates/mfa_verify.html.twig', [
            'user' => $user,
            'errorMessage' => $errorMessage,
            'mfaType' => $mfaType,
        ]);
    }

    #[Route('/user/mfa/verify', name: 'mfa_verify_post', methods: ['POST'])]
    public function verifyMfa() : \Symfony\Component\HttpFoundation\Response
    {
        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/login');
        }

        $code = $_POST['code'] ?? '';

        if (empty($code)) {
            $_SESSION['mfa_error'] = 'Будь ласка, введіть код.';
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/mfa/verify');
        }

        if ($this->mfaService->verifyUserMfa($userId, $code)) {
            unset($_SESSION['mfa_pending_user_id']);
            MfaGuard::clearRequired();

            $user = $this->userRepository->findById($userId);
            $role = $this->roleRepository->findById((int)$user['role_id']);

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

            return new \Symfony\Component\HttpFoundation\RedirectResponse($redirect);
        } else {
            $_SESSION['mfa_error'] = 'Невірний код. Спробуйте ще раз.';
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/mfa/verify');
        }
    }

    #[Route('/user/mfa/backup-codes/regenerate', name: 'mfa_regenerate_backup_codes', methods: ['POST'])]
    public function regenerateBackupCodes() : \Symfony\Component\HttpFoundation\Response
    {
        $this->checkAuth();

        $userId = $_SESSION['user']['id'];
        $password = $_POST['password'] ?? '';

        $user = $this->userRepository->findById($userId);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['error_message'] = 'Невірний пароль.';
            header('Location: /user/profile');
            exit();
        }

        if (!$this->mfaService->isMfaEnabled($userId)) {
            return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
        }

        $backupCodes = $this->mfaService->generateBackupCodes();

        $conn = $this->registry->getConnection();
        $conn->executeStatement("
            UPDATE users SET mfa_backup_codes = :codes WHERE id = :id
        ", ['id' => $userId, 'codes' => json_encode($backupCodes)]);

        $_SESSION['new_backup_codes'] = $backupCodes;
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
    }

    #[Route('/user/mfa/backup-codes/clear', name: 'mfa_clear_backup_codes', methods: ['GET', 'POST'])]
    public function clearNewBackupCodes() : \Symfony\Component\HttpFoundation\Response
    {
        unset($_SESSION['new_backup_codes']);
        return new \Symfony\Component\HttpFoundation\RedirectResponse('/user/profile');
    }
}

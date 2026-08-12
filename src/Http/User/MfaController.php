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

use App\Auth\MfaGuard;
use App\Domain\User\MfaService;
use App\Domain\User\RoleRepository;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use App\Shared\Repository\SettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class MfaController extends AbstractController
{
    private MfaService $mfaService;
    private UserRepository $userRepository;
    private SettingsRepository $settingsRepository;
    private RoleRepository $roleRepository;
    private \Doctrine\Persistence\ManagerRegistry $registry;
    private MfaGuard $mfaGuard;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        MfaService $mfaService,
        UserRepository $userRepository,
        SettingsRepository $settingsRepository,
        RoleRepository $roleRepository,
        \Doctrine\Persistence\ManagerRegistry $registry,
        MfaGuard $mfaGuard,
        TokenStorageInterface $tokenStorage
    ) {
        $this->mfaService = $mfaService;
        $this->userRepository = $userRepository;
        $this->settingsRepository = $settingsRepository;
        $this->roleRepository = $roleRepository;
        $this->registry = $registry;
        $this->mfaGuard = $mfaGuard;
        $this->tokenStorage = $tokenStorage;
    }

    private function authenticateUser(int $userId) : void
    {
        /** @var \App\Domain\User\User|null $userEntity */
        $userEntity = $this->userRepository->find($userId);
        if ($userEntity) {
            $token = new UsernamePasswordToken($userEntity, 'main', $userEntity->getRoles());
            $this->tokenStorage->setToken($token);
        }
        $_SESSION['user_id'] = $userId;
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
    public function showMfaSetup(string $type = 'totp', #[CurrentUser] ?User $currentUser = null) : Response
    {
        if (!in_array($type, ['totp', 'hotp'], true)) {
            $type = 'totp';
        }

        if ($currentUser) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            $mfaPolicy = $this->settingsRepository->getMfaPolicy();

            if ('disabled' === $mfaPolicy) {
                $_SESSION['error_message'] = 'Двофакторна автентифікація вимкнена в налаштуваннях системи.';
                return $this->redirectToRoute('user_profile');
            }
        }

        $userId = $_SESSION['mfa_pending_user_id'] ?? ($currentUser?->getId());

        if (!$userId) {
            return $this->redirectToRoute('login_form');
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            session_destroy();
            return $this->redirectToRoute('login_form');
        }

        $isReset = isset($_GET['reset']) && '1' === $_GET['reset'];

        if ($this->mfaService->isMfaEnabled($userId) && !$isReset) {
            return $this->redirectToRoute('user_profile');
        }

        if ('hotp' === $type) {
            $secret = [];
            $backupCodes = [];
            $counter = 0;
            $qrCode = '';
            $this->prepareHotpSetup($userId, $secret, $backupCodes, $counter, $qrCode);

            return $this->render('user/hotp_setup.html.twig', [
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

            return $this->render('user/mfa_setup.html.twig', [
                'user' => $user,
                'secret' => $secret,
                'qrCode' => $qrCode,
                'backupCodes' => $backupCodes,
                'isReset' => $isReset,
            ]);
        }
    }

    #[Route('/user/mfa/required', name: 'mfa_required_choice', methods: ['GET'])]
    public function showMfaRequiredChoice() : Response
    {
        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            return $this->redirectToRoute('login_form');
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            session_destroy();
            return $this->redirectToRoute('login_form');
        }

        return $this->render('user/mfa_required_choice.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/mfa/required/{type}', name: 'mfa_required', methods: ['GET'], defaults: ['type' => 'totp'])]
    public function showMfaRequired(string $type = 'totp') : Response
    {
        if (!in_array($type, ['totp', 'hotp'], true)) {
            return $this->redirectToRoute('mfa_required_choice');
        }

        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            return $this->redirectToRoute('login_form');
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            session_destroy();
            return $this->redirectToRoute('login_form');
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

            return $this->render('user/hotp_required.html.twig', [
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

            return $this->render('user/totp_required.html.twig', [
                'user' => $user,
                'secret' => $secret,
                'qrCode' => $qrCode,
                'backupCodes' => $backupCodes,
            ]);
        }
    }

    #[Route('/user/mfa/setup/{type}', name: 'mfa_setup_verify', methods: ['POST'], defaults: ['type' => 'totp'])]
    public function verifyMfaSetup(string $type = 'totp', #[CurrentUser] ?User $currentUser = null) : Response
    {
        if (!in_array($type, ['totp', 'hotp'], true)) {
            $type = 'totp';
        }

        if ($currentUser) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            $mfaPolicy = $this->settingsRepository->getMfaPolicy();

            if ('disabled' === $mfaPolicy) {
                $_SESSION['error_message'] = 'Двофакторна автентифікація вимкнена в налаштуваннях системи.';
                return $this->redirectToRoute('user_profile');
            }
        }

        $userId = $_SESSION['mfa_pending_user_id'] ?? ($currentUser?->getId());

        if (!$userId) {
            return $this->redirectToRoute('login_form');
        }

        $isReset = isset($_GET['reset']) && '1' === $_GET['reset'];

        if ('hotp' === $type) {
            $secret = $_SESSION['hotp_setup_secret'] ?? null;
            $backupCodes = $_SESSION['hotp_setup_backup_codes'] ?? [];
            $counter = $_SESSION['hotp_setup_counter'] ?? 0;

            if (!$secret) {
                $redirectUrl = $isReset ? '/user/mfa/setup/hotp?reset=1' : '/user/mfa/setup/hotp';
                return $this->redirectToRoute('mfa_setup', ['type' => 'hotp', 'reset' => 1]);
            }

            $code = $_POST['code'] ?? '';

            if (empty($code)) {
                $_SESSION['error_message'] = 'Будь ласка, введіть код.';
                $redirectUrl = $isReset ? '/user/mfa/setup/hotp?reset=1' : '/user/mfa/setup/hotp';
                return $this->redirectToRoute('mfa_setup', ['type' => 'hotp', 'reset' => 1]);
            }

            $lastCounter = $_SESSION['hotp_setup_last_counter'] ?? 0;
            $verifiedCounter = $this->mfaService->verifyHotpCodeWithCounter($secret, $code, $counter, $lastCounter);

            if (null !== $verifiedCounter) {
                $_SESSION['hotp_setup_last_counter'] = $verifiedCounter;
                $this->mfaService->enableHotpForUser($userId, $secret, $backupCodes, $verifiedCounter + 1);

                unset($_SESSION['hotp_setup_secret'], $_SESSION['hotp_setup_backup_codes'], $_SESSION['hotp_setup_counter'], $_SESSION['hotp_setup_last_counter']);

                if ($this->getUser()) {
                    $_SESSION['success_message'] = 'Двофакторну автентифікацію HOTP успішно увімкнено!';
                    return $this->redirectToRoute('user_profile');
                } else {
                    $this->mfaGuard->clearRequired();
                    $this->authenticateUser($userId);

                    $redirect = $_SESSION['intended_url'] ?? null;
                    unset($_SESSION['intended_url']);
                    if ($redirect) {
                        return new RedirectResponse($redirect);
                    }

                    return $this->redirectToRoute('dashboard');
                }
            } else {
                $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
                $redirectUrl = $isReset ? '/user/mfa/setup/hotp?reset=1' : '/user/mfa/setup/hotp';
                return $this->redirectToRoute('mfa_setup', ['type' => 'hotp', 'reset' => 1]);
            }
        } else {
            $secret = $_SESSION['mfa_setup_secret'] ?? null;
            $backupCodes = $_SESSION['mfa_setup_backup_codes'] ?? [];

            if (!$secret) {
                $redirectUrl = $isReset ? '/user/mfa/setup/totp?reset=1' : '/user/mfa/setup/totp';
                return $this->redirectToRoute('mfa_setup', ['type' => 'hotp', 'reset' => 1]);
            }

            $code = $_POST['code'] ?? '';

            if (empty($code)) {
                $_SESSION['error_message'] = 'Будь ласка, введіть код з додатку.';
                $redirectUrl = $isReset ? '/user/mfa/setup/totp?reset=1' : '/user/mfa/setup/totp';
                return $this->redirectToRoute('mfa_setup', ['type' => 'hotp', 'reset' => 1]);
            }

            if ($this->mfaService->verifyCode($secret, $code)) {
                $this->mfaService->enableMfaForUser($userId, $secret, $backupCodes, 'totp');

                unset($_SESSION['mfa_setup_secret'], $_SESSION['mfa_setup_backup_codes']);

                if ($this->getUser()) {
                    $_SESSION['success_message'] = 'Двофакторну автентифікацію успішно увімкнено!';
                    return $this->redirectToRoute('user_profile');
                } else {
                    $this->mfaGuard->clearRequired();
                    $this->authenticateUser($userId);

                    $redirect = $_SESSION['intended_url'] ?? null;
                    unset($_SESSION['intended_url']);
                    if ($redirect) {
                        return new RedirectResponse($redirect);
                    }

                    return $this->redirectToRoute('dashboard');
                }
            } else {
                $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
                $redirectUrl = $isReset ? '/user/mfa/setup/totp?reset=1' : '/user/mfa/setup/totp';
                return $this->redirectToRoute('mfa_setup', ['type' => 'hotp', 'reset' => 1]);
            }
        }
    }

    #[Route('/user/mfa/required/{type}', name: 'mfa_required_verify', methods: ['POST'], defaults: ['type' => 'totp'])]
    public function verifyMfaRequired(string $type = 'totp') : Response
    {
        if (!in_array($type, ['totp', 'hotp'], true)) {
            return $this->redirectToRoute('mfa_required_choice');
        }

        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            return $this->redirectToRoute('login_form');
        }

        if ('hotp' === $type) {
            $secret = $_SESSION['hotp_setup_secret'] ?? null;
            $backupCodes = $_SESSION['hotp_setup_backup_codes'] ?? [];
            $counter = $_SESSION['hotp_setup_counter'] ?? 0;

            if (!$secret) {
                $_SESSION['error_message'] = 'Помилка генерування коду. Спробуйте ще раз.';
                return $this->redirectToRoute('mfa_required', ['type' => 'hotp']);
            }

            $code = $_POST['code'] ?? '';

            if (empty($code)) {
                $_SESSION['error_message'] = 'Будь ласка, введіть код.';
                return $this->redirectToRoute('mfa_required', ['type' => 'hotp']);
            }

            $lastCounter = $_SESSION['hotp_setup_last_counter'] ?? 0;
            $verifiedCounter = $this->mfaService->verifyHotpCodeWithCounter($secret, $code, $counter, $lastCounter);

            if (null !== $verifiedCounter) {
                $_SESSION['hotp_setup_last_counter'] = $verifiedCounter;
                $this->mfaService->enableHotpForUser($userId, $secret, $backupCodes, $verifiedCounter + 1);

                unset($_SESSION['hotp_setup_secret'], $_SESSION['hotp_setup_backup_codes'], $_SESSION['hotp_setup_counter'], $_SESSION['hotp_setup_last_counter']);
                $this->mfaGuard->clearRequired();

                $this->authenticateUser($userId);

                $redirect = $_SESSION['intended_url'] ?? null;
                unset($_SESSION['intended_url']);
                if ($redirect) {
                    return new RedirectResponse($redirect);
                }

                return $this->redirectToRoute('dashboard');
            } else {
                $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
                return $this->redirectToRoute('mfa_required', ['type' => 'hotp']);
            }
        } else {
            $secret = $_SESSION['mfa_setup_secret'] ?? null;
            $backupCodes = $_SESSION['mfa_setup_backup_codes'] ?? [];

            if (!$secret) {
                $_SESSION['error_message'] = 'Помилка генерування коду. Спробуйте ще раз.';
                return $this->redirectToRoute('mfa_required', ['type' => 'totp']);
            }

            $code = $_POST['code'] ?? '';

            if (empty($code)) {
                $_SESSION['error_message'] = 'Будь ласка, введіть код з додатку.';
                return $this->redirectToRoute('mfa_required', ['type' => 'totp']);
            }

            if ($this->mfaService->verifyCode($secret, $code)) {
                $this->mfaService->enableMfaForUser($userId, $secret, $backupCodes, 'totp');

                unset($_SESSION['mfa_setup_secret'], $_SESSION['mfa_setup_backup_codes']);
                $this->mfaGuard->clearRequired();

                $this->authenticateUser($userId);

                $redirect = $_SESSION['intended_url'] ?? null;
                unset($_SESSION['intended_url']);
                if ($redirect) {
                    return new RedirectResponse($redirect);
                }

                return $this->redirectToRoute('dashboard');
            } else {
                $_SESSION['error_message'] = 'Невірний код. Спробуйте ще раз.';
                return $this->redirectToRoute('mfa_required', ['type' => 'totp']);
            }
        }
    }

    #[Route('/user/mfa/disable', name: 'mfa_disable', methods: ['POST'])]
    public function disableMfa(#[CurrentUser] User $user) : Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $userId = $user->getId();
        $password = $_POST['password'] ?? '';

        $user = $this->userRepository->findById($userId);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['error_message'] = 'Невірний пароль.';
            header('Location: /user/profile');
            exit();
        }

        $this->mfaService->disableMfaForUser($userId);

        $_SESSION['success_message'] = 'Двофакторну автентифікацію вимкнено.';
        return $this->redirectToRoute('user_profile');
    }

    #[Route('/user/mfa/verify', name: 'mfa_verify', methods: ['GET'])]
    public function showMfaVerify() : Response
    {
        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            return $this->redirectToRoute('login_form');
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            session_destroy();
            return $this->redirectToRoute('login_form');
        }

        $errorMessage = $_SESSION['mfa_error'] ?? null;
        unset($_SESSION['mfa_error']);

        $mfaType = $_SESSION['mfa_type'] ?? $this->mfaService->getUserMfaStatus($userId)['type'] ?? 'totp';

        return $this->render('user/mfa_verify.html.twig', [
            'user' => $user,
            'errorMessage' => $errorMessage,
            'mfaType' => $mfaType,
        ]);
    }

    #[Route('/user/mfa/verify', name: 'mfa_verify_post', methods: ['POST'])]
    public function verifyMfa() : Response
    {
        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$userId) {
            return $this->redirectToRoute('login_form');
        }

        $code = $_POST['code'] ?? '';

        if (empty($code)) {
            $_SESSION['mfa_error'] = 'Будь ласка, введіть код.';
            return $this->redirectToRoute('mfa_verify');
        }

        if ($this->mfaService->verifyUserMfa($userId, $code)) {
            unset($_SESSION['mfa_pending_user_id']);
            $this->mfaGuard->clearRequired();

            $this->authenticateUser($userId);

            $redirect = $_SESSION['intended_url'] ?? null;
            unset($_SESSION['intended_url']);
            if ($redirect) {
                return new RedirectResponse($redirect);
            }

            return $this->redirectToRoute('dashboard');
        } else {
            $_SESSION['mfa_error'] = 'Невірний код. Спробуйте ще раз.';
            return $this->redirectToRoute('mfa_verify');
        }
    }

    #[Route('/user/mfa/regenerate-backup-codes', name: 'mfa_regenerate_backup_codes', methods: ['POST'])]
    public function regenerateBackupCodes(#[CurrentUser] User $user) : Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $userId = $user->getId();
        $password = $_POST['password'] ?? '';

        $user = $this->userRepository->findById($userId);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['error_message'] = 'Невірний пароль.';
            header('Location: /user/profile');
            exit();
        }

        if (!$this->mfaService->isMfaEnabled($userId)) {
            return $this->redirectToRoute('user_profile');
        }

        $backupCodes = $this->mfaService->generateBackupCodes();

        $conn = $this->registry->getConnection();
        $conn->executeStatement("
            UPDATE users SET mfa_backup_codes = :codes WHERE id = :id
        ", ['id' => $userId, 'codes' => json_encode($backupCodes)]);

        $_SESSION['new_backup_codes'] = $backupCodes;
        return $this->redirectToRoute('user_profile');
    }

    #[Route('/user/mfa/clear-backup-codes', name: 'mfa_clear_backup_codes', methods: ['GET', 'POST'])]
    public function clearNewBackupCodes() : Response
    {
        unset($_SESSION['new_backup_codes']);
        return $this->redirectToRoute('user_profile');
    }
}

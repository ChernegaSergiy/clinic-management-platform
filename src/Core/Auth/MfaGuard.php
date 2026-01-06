<?php

namespace App\Core\Auth;

use App\Module\User\MfaService;

class MfaGuard
{
    public static function check(): void
    {
        $step = AuthStep::current();

        if ($step->requiresMfaVerify()) {
            $userId = $_SESSION['mfa_pending_user_id'] ?? null;
            $mfaService = new MfaService();

            if ($mfaService->isMfaEnabled($userId)) {
                header('Location: /user/mfa/verify');
                exit();
            } else {
                self::clearPending();
            }
        } elseif ($step->requiresMfaSetup()) {
            header('Location: /user/mfa/setup');
            exit();
        }
    }

    public static function isPending(): bool
    {
        return isset($_SESSION['mfa_pending_user_id']);
    }

    public static function getPendingUserId(): ?int
    {
        return $_SESSION['mfa_pending_user_id'] ?? null;
    }

    public static function clearPending(): void
    {
        unset($_SESSION['mfa_pending_user_id']);
    }

    public static function isRequired(): bool
    {
        return isset($_SESSION['mfa_required']) && $_SESSION['mfa_required'] === true;
    }

    public static function setRequired(): void
    {
        $_SESSION['mfa_required'] = true;
    }

    public static function clearRequired(): void
    {
        unset($_SESSION['mfa_required']);
    }
}

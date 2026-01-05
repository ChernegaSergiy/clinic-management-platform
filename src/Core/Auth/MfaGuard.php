<?php

namespace App\Core\Auth;

use App\Module\User\MfaService;

class MfaGuard
{
    public static function check(): void
    {
        $userId = $_SESSION['mfa_pending_user_id'] ?? null;

        if ($userId === null) {
            return;
        }

        $mfaService = new MfaService();

        if ($mfaService->isMfaEnabled($userId)) {
            header('Location: /user/mfa/verify');
            exit();
        } else {
            unset($_SESSION['mfa_pending_user_id']);
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
}

<?php

namespace App\Core\Auth;

use App\Core\Exception\RedirectException;
use App\Module\User\MfaService;

class MfaGuard
{
    private MfaService $mfaService;

    public function __construct(MfaService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    public function check(): void
    {
        $step = AuthStep::current();
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        if ($step->requiresMfaVerify()) {
            $userId = $_SESSION['mfa_pending_user_id'] ?? null;

            if ($this->mfaService->isMfaEnabled($userId)) {
                throw new RedirectException('/user/mfa/verify');
            } else {
                $this->clearPending();
            }
        } elseif ($step->requiresMfaSetup()) {
            if ($this->isRequired() && !str_starts_with($requestUri, '/user/mfa/')) {
                throw new RedirectException('/user/mfa/required');
            }
        }
    }

    public function isPending(): bool
    {
        return isset($_SESSION['mfa_pending_user_id']);
    }

    public function getPendingUserId(): ?int
    {
        return $_SESSION['mfa_pending_user_id'] ?? null;
    }

    public function clearPending(): void
    {
        unset($_SESSION['mfa_pending_user_id']);
    }

    public function isRequired(): bool
    {
        return isset($_SESSION['mfa_required']) && $_SESSION['mfa_required'] === true;
    }

    public function setRequired(): void
    {
        $_SESSION['mfa_required'] = true;
    }

    public function clearRequired(): void
    {
        unset($_SESSION['mfa_required']);
    }

    public function getUserMfaType(int $userId): ?string
    {
        $status = $this->mfaService->getUserMfaStatus($userId);
        return $status['type'] ?? null;
    }

    public function isHotpEnabled(int $userId): bool
    {
        return $this->getUserMfaType($userId) === 'hotp';
    }
}

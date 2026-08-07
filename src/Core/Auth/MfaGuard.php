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

namespace App\Core\Auth;

use App\Bundles\UserBundle\Service\MfaService;
use App\Core\Exception\RedirectException;

class MfaGuard
{
    private MfaService $mfaService;

    public function __construct(MfaService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    public function check() : void
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

    public function isPending() : bool
    {
        return isset($_SESSION['mfa_pending_user_id']);
    }

    public function getPendingUserId() : ?int
    {
        return $_SESSION['mfa_pending_user_id'] ?? null;
    }

    public function clearPending() : void
    {
        unset($_SESSION['mfa_pending_user_id']);
    }

    public function isRequired() : bool
    {
        return isset($_SESSION['mfa_required']) && true === $_SESSION['mfa_required'];
    }

    public function setRequired() : void
    {
        $_SESSION['mfa_required'] = true;
    }

    public function clearRequired() : void
    {
        unset($_SESSION['mfa_required']);
    }

    public function getUserMfaType(int $userId) : ?string
    {
        $status = $this->mfaService->getUserMfaStatus($userId);
        return $status['type'] ?? null;
    }

    public function isHotpEnabled(int $userId) : bool
    {
        return 'hotp' === $this->getUserMfaType($userId);
    }
}

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

use App\Bundles\UserBundle\Repository\RoleRepositoryInterface;
use App\Core\Exception\ExitException;
use App\Core\Exception\RedirectException;

class AuthGuard
{
    private RoleRepositoryInterface $roleRepository;
    private MfaGuard $mfaGuard;

    public function __construct(RoleRepositoryInterface $roleRepository, MfaGuard $mfaGuard)
    {
        $this->roleRepository = $roleRepository;
        $this->mfaGuard = $mfaGuard;
    }

    private function hydrateRoleName() : void
    {
        if (empty($_SESSION['user']) || !empty($_SESSION['user']['role_name'])) {
            return;
        }

        $roleId = $_SESSION['user']['role_id'] ?? null;
        if ($roleId) {
            $role = $this->roleRepository->findById((int)$roleId);
            $_SESSION['user']['role_name'] = $role['name'] ?? null;
        }
    }

    public function check() : void
    {
        $step = AuthStep::current();

        if ($step->isAuthorized()) {
            $this->mfaGuard->check();
            $this->hydrateRoleName();
            return;
        }

        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/dashboard';
        throw new RedirectException('/login');
    }

    public function requireMfaSetup() : void
    {
        $step = AuthStep::current();

        if (!$step->requiresMfaSetup() && !$step->requiresMfaVerify()) {
            throw new RedirectException('/login');
        }
    }

    public function isAdmin() : void
    {
        $this->check();
        if (1 !== $_SESSION['user']['role_id']) {
            throw new ExitException("Доступ заборонено", 403);
        }
    }
}

<?php

namespace App\Core\Auth;

use App\Core\Exception\ExitException;
use App\Core\Exception\RedirectException;
use App\Module\User\Repository\RoleRepository;

class AuthGuard
{
    private static ?RoleRepository $roleRepository = null;

    public static function setRoleRepository(RoleRepository $repository): void
    {
        self::$roleRepository = $repository;
    }

    public static function resetRoleRepository(): void
    {
        self::$roleRepository = null;
    }

    private static function roles(): RoleRepository
    {
        if (!self::$roleRepository) {
            self::$roleRepository = \App\Kernel::$staticContainer->get(\App\Module\User\Repository\RoleRepositoryInterface::class);
        }
        return self::$roleRepository;
    }

    private static function hydrateRoleName(): void
    {
        if (empty($_SESSION['user']) || !empty($_SESSION['user']['role_name'])) {
            return;
        }

        $roleId = $_SESSION['user']['role_id'] ?? null;
        if ($roleId) {
            $role = self::roles()->findById((int)$roleId);
            $_SESSION['user']['role_name'] = $role['name'] ?? null;
        }
    }

    public static function check(): void
    {
        $step = AuthStep::current();

        if ($step->isAuthorized()) {
            MfaGuard::check();
            self::hydrateRoleName();
            return;
        }

        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/dashboard';
        throw new RedirectException('/login');
    }

    public static function requireMfaSetup(): void
    {
        $step = AuthStep::current();

        if (!$step->requiresMfaSetup() && !$step->requiresMfaVerify()) {
            throw new RedirectException('/login');
        }
    }

    public static function isAdmin(): void
    {
        self::check();
        if ($_SESSION['user']['role_id'] !== 1) {
            throw new ExitException("Доступ заборонено", 403);
        }
    }
}

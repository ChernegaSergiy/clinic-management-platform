<?php

namespace App\Core\Auth;

use App\Module\User\Repository\RoleRepository;

class AuthGuard
{
    private static ?RoleRepository $roleRepository = null;

    private static function roles(): RoleRepository
    {
        if (!self::$roleRepository) {
            self::$roleRepository = new RoleRepository();
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
        header('Location: /login');
        exit();
    }

    public static function requireMfaSetup(): void
    {
        $step = AuthStep::current();

        if (!$step->requiresMfaSetup() && !$step->requiresMfaVerify()) {
            header('Location: /login');
            exit();
        }
    }

    public static function isAdmin(): void
    {
        self::check();
        if ($_SESSION['user']['role_id'] !== 1) {
            http_response_code(403);
            echo "Доступ заборонено";
            exit();
        }
    }
}

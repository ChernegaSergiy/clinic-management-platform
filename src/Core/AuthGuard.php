<?php

namespace App\Core;

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
        if (!isset($_SESSION['user'])) {
            // Remember intended URL to return after login
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/dashboard';
            header('Location: /login');
            exit();
        }
        self::hydrateRoleName();
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

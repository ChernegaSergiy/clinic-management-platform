<?php

namespace App\Core;

class AuthGuard
{
    public static function check(): void
    {
        if (!isset($_SESSION['user'])) {
            // Remember intended URL to return after login
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/dashboard';
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

<?php

namespace App\Controller;

use App\Core\View;

class AuthController
{
    public function showLoginForm(): void
    {
        View::render('auth/login.html.twig');
    }

    public function login(): void
    {
        $users = [
            'admin' => password_hash('password', PASSWORD_BCRYPT),
            'doctor' => password_hash('password', PASSWORD_BCRYPT),
        ];

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $error = [];

        if (isset($users[$username]) && password_verify($password, $users[$username])) {
            $_SESSION['user'] = $username;
            header('Location: /dashboard');
            exit();
        } else {
            $error['message'] = 'Невірне ім\'я користувача або пароль.';
        }
        View::render('auth/login.html.twig', ['error' => $error]);
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: /');
        exit();
    }

    public function dashboard(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        echo "<h1>Вітаємо, " . $_SESSION['user'] . "! Це ваш дашборд.</h1><p><a href=\"/logout\">Вийти</a></p>";
    }
}

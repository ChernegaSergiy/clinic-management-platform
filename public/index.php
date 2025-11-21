<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

use Symfony\Component\Yaml\Yaml;

// Завантаження .env файлу
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

session_start();

// Налаштування завантажувача шаблонів
$loader = new FilesystemLoader(__DIR__ . '/../templates');

// Налаштування середовища Twig
$twig = new Environment($loader, [
    // 'cache' => __DIR__ . '/../var/cache', // Розкоментуйте для кешування
]);
$twig->addGlobal('session', $_SESSION);

// Завантаження контенту
$content = Yaml::parseFile(__DIR__ . '/../content/home.uk.yml');

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Проста імітація користувачів
$users = [
    'admin' => password_hash('password', PASSWORD_BCRYPT),
    'doctor' => password_hash('password', PASSWORD_BCRYPT),
];

// Маршрутизація
switch ($requestUri) {
    case '/':
        echo $twig->render('home/index.html.twig', ['page' => $content]);
        break;
    case '/about':
        echo $twig->render('about/index.html.twig');
        break;
    case '/contact':
        echo $twig->render('contact/index.html.twig');
        break;
    case '/login':
        if ($requestMethod === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $error = [];

            if (isset($users[$username]) && password_verify($password, $users[$username])) {
                $_SESSION['user'] = $username;
                header('Location: /dashboard'); // Перенаправлення на дашборд після успішного входу
                exit();
            } else {
                $error['message'] = 'Невірне ім\'я користувача або пароль.';
            }
            echo $twig->render('auth/login.html.twig', ['error' => $error]);
        } else {
            echo $twig->render('auth/login.html.twig');
        }
        break;
    case '/logout':
        session_destroy();
        header('Location: /');
        exit();
        break;
    case '/patients':
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        $patients = [
            ['id' => 1, 'name' => 'Іван Петренко', 'birth_date' => '1985-05-20', 'phone' => '+380501234567'],
            ['id' => 2, 'name' => 'Марія Іваненко', 'birth_date' => '1992-08-15', 'phone' => '+380671234567'],
            ['id' => 3, 'name' => 'Олена Сидоренко', 'birth_date' => '1978-11-30', 'phone' => '+380931234567'],
        ];
        echo $twig->render('patients/index.html.twig', ['patients' => $patients]);
        break;
    case '/dashboard': // Приклад захищеного маршруту
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }
        echo "<h1>Вітаємо, " . $_SESSION['user'] . "! Це ваш дашборд.</h1><p><a href=\"/logout\">Вийти</a></p>";
        break;
    default:
        header("HTTP/1.0 404 Not Found");
        echo $twig->render('404.html.twig'); // Потрібно буде створити 404.html.twig
        break;
}

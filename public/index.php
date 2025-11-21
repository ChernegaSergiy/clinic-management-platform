<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Налаштування завантажувача шаблонів
$loader = new FilesystemLoader(__DIR__ . '/../templates');

// Налаштування середовища Twig
$twig = new Environment($loader, [
    // 'cache' => __DIR__ . '/../var/cache', // Розкоментуйте для кешування
]);


$requestUri = $_SERVER['REQUEST_URI'];

switch ($requestUri) {
    case '/':
        echo $twig->render('home/index.html.twig', ['name' => 'World']);
        break;
    case '/about':
        echo $twig->render('about/index.html.twig');
        break;
    default:
        header("HTTP/1.0 404 Not Found");
        echo $twig->render('404.html.twig'); // Потрібно буде створити 404.html.twig
        break;
}

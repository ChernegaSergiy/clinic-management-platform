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

// Рендеринг шаблону
echo $twig->render('index.html.twig', ['name' => 'World']);

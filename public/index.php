<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Controller\PageController;
use App\Controller\AuthController;
use App\Controller\PatientController;

// Завантаження .env файлу
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

session_start();

$router = new Router();

// Page routes
$router->add('GET', '/', [PageController::class, 'home']);
$router->add('GET', '/about', [PageController::class, 'about']);
$router->add('GET', '/contact', [PageController::class, 'contact']);

// Auth routes
$router->add('GET', '/login', [AuthController::class, 'showLoginForm']);
$router->add('POST', '/login', [AuthController::class, 'login']);
$router->add('GET', '/logout', [AuthController::class, 'logout']);
$router->add('GET', '/dashboard', [AuthController::class, 'dashboard']);

// Patient routes
$router->add('GET', '/patients', [PatientController::class, 'index']);
$router->add('GET', '/patients/new', [PatientController::class, 'create']);
$router->add('POST', '/patients/new', [PatientController::class, 'store']);
$router->add('GET', '/patients/show', [PatientController::class, 'show']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\InstallController;
use App\Controller\PageController;
use App\Core\Http\Router;
use App\Core\Http\View;
use App\Core\Module\ModuleLoader;
use App\Core\Module\ModuleManager;

if (!isset($_ENV['APP_BASE_URL']) || empty($_ENV['APP_BASE_URL'])) {
    $envPath = __DIR__ . '/../.env';
    if (file_exists($envPath)) {
        Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
    }
}

$requestedPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$staticFile = realpath(__DIR__ . $requestedPath);
if ($staticFile && str_starts_with($staticFile, realpath(__DIR__)) && is_file($staticFile)) {
    $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
    $mimeMap = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];
    $mime = $mimeMap[$ext] ?? mime_content_type($staticFile) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    readfile($staticFile);
    exit;
}

if (isset($_ENV['APP_BASE_URL']) && !empty($_ENV['APP_BASE_URL'])) {
    $appUrlParts = parse_url($_ENV['APP_BASE_URL']);
    if (isset($appUrlParts['host'])) {
        $cookieParams = [
            'lifetime' => 0,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        session_set_cookie_params($cookieParams);
    }
}
session_start();

$router = new Router();

$permissionRegistry = new \App\Core\Auth\PermissionRegistry();
$policyRegistry = new \App\Core\Auth\PolicyRegistry();

$moduleManager = new ModuleManager();
$moduleLoader = new ModuleLoader($moduleManager);
$moduleLoader->loadAll();

$moduleManager->bootstrapAll();

$moduleManager->registerPermissions($permissionRegistry);
$moduleManager->registerPolicies($policyRegistry);
$moduleManager->registerRoutes($router);

\App\Core\Auth\Gate::setPermissionRegistry($permissionRegistry);
\App\Core\Auth\Gate::setPolicyRegistry($policyRegistry);

$router->add('GET', '/', [PageController::class, 'home']);
$router->add('GET', '/about', [PageController::class, 'about']);
$router->add('GET', '/our-team', [PageController::class, 'ourTeam']);
$router->add('GET', '/contact', [PageController::class, 'contact']);
$router->add('GET', '/sitemap', [PageController::class, 'sitemap']);
$router->add('GET', '/privacy', [PageController::class, 'privacy']);
$router->add('GET', '/departments', [PageController::class, 'departments']);
$router->add('GET', '/doctors', [PageController::class, 'doctors']);

$router->add('GET', '/install', [InstallController::class, 'check']);

$installed = $_ENV['APP_INSTALLED'] ?? false;
if (!$installed && parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/install') {
    header('Location: /install');
    exit;
}

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (\Throwable $e) {
    http_response_code(500);
    $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    View::render('errors/error.html.twig', [
        'message' => 'Щось пішло не так. Ми вже розбираємося.',
        'detail' => $isDebug ? $e->getMessage() : null,
    ]);
}

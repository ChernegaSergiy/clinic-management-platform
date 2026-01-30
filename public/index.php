<?php

$requestedPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$resource = __DIR__ . $requestedPath;

if (file_exists($resource)) {
    // Якщо це директорія з індексним файлом (окрім кореневої public), дозволяємо серверу обробити її
    if (is_dir($resource) && realpath($resource) !== __DIR__ && (file_exists($resource . '/index.php') || file_exists($resource . '/index.html'))) {
        return false;
    }

    // Якщо це файл (і не цей самий скрипт), дозволяємо серверу обробити його
    if (is_file($resource) && realpath($resource) !== __FILE__) {
        return false;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\InstallController;
use App\Controller\PageController;
use App\Core\Exception\ExitException;
use App\Core\Exception\RedirectException;
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
} catch (RedirectException $e) {
    header('Location: ' . $e->getUrl());
    exit;
} catch (ExitException $e) {
    http_response_code($e->getStatusCode());
    if ($e->getExitMessage() !== '') {
        echo $e->getExitMessage();
    }
    exit;
} catch (\Throwable $e) {
    http_response_code(500);
    $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    View::render('errors/error.html.twig', [
        'message' => 'Щось пішло не так. Ми вже розбираємося.',
        'detail' => $isDebug ? $e->getMessage() : null,
    ]);
}
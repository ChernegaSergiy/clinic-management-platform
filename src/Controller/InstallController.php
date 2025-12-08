<?php

namespace App\Controller;

use App\Core\View;
use PDO;

class InstallController
{
    public function check(): void
    {
        $checks = [];

        // 1. Check PHP Version & Extensions
        $checks['php_version'] = $this->checkPhpVersion();
        $checks['php_extensions'] = $this->checkPhpExtensions(['pdo_mysql', 'mbstring', 'intl', 'gd']);

        // 2. Check Directory Permissions
        $checks['dir_permissions'] = $this->checkDirectoryPermissions([
            'public/uploads' => 'writable',
            'vendor' => 'readable',
        ]);

        // 3. Check .env file
        $envCheck = $this->checkEnvFile();
        $checks['env_file'] = $envCheck;

        // Subsequent checks depend on the .env file
        $dbConfig = [];
        if ($envCheck['status']) {
            // 4. Check Database Connection
            $dbConfig = $this->loadDbConfigFromEnv();
            $checks['db_connection'] = $this->checkDbConnection($dbConfig);

            // 5. Check Migration Status
            if ($checks['db_connection']['status']) {
                $checks['migrations'] = $this->checkMigrationStatus();
                $checks['seeding'] = $this->checkSeedingStatus($dbConfig);
            }
        }
        
        $allOk = array_reduce($checks, function ($carry, $check) {
            if (isset($check['status'])) {
                return $carry && $check['status'];
            }
            // Handle nested checks like extensions
            foreach ($check as $subCheck) {
                if (!$subCheck['status']) return false;
            }
            return $carry;
        }, true);

        View::render('install/index.html.twig', [
            'checks' => $checks,
            'allOk' => $allOk,
        ]);
    }

    private function checkPhpVersion(): array
    {
        $requiredVersion = '8.2';
        $status = version_compare(PHP_VERSION, $requiredVersion, '>=');
        return [
            'status' => $status,
            'message' => $status ? 'PHP ' . PHP_VERSION : "Потрібна версія PHP >= {$requiredVersion}. Ваша версія: " . PHP_VERSION,
        ];
    }

    private function checkPhpExtensions(array $extensions): array
    {
        $results = [];
        foreach ($extensions as $extension) {
            $status = extension_loaded($extension);
            $results[$extension] = [
                'status' => $status,
                'message' => $status ? "Розширення '{$extension}' завантажено" : "Розширення '{$extension}' не знайдено",
            ];
        }
        return $results;
    }

    private function checkDirectoryPermissions(array $dirs): array
    {
        $results = [];
        foreach ($dirs as $dir => $permission) {
            $path = __DIR__ . '/../../' . $dir;
            $status = false;
            $message = "Каталог '{$dir}' не знайдено.";
            if (file_exists($path)) {
                if ($permission === 'writable') {
                    $status = is_writable($path);
                    $message = $status ? "Каталог '{$dir}' доступний для запису" : "Каталог '{$dir}' НЕ доступний для запису. Виконайте `chmod -R 775 {$dir}`";
                } elseif ($permission === 'readable') {
                    $status = is_readable($path);
                    $message = $status ? "Каталог '{$dir}' доступний для читання" : "Каталог '{$dir}' НЕ доступний для читання.";
                }
            }
            $results[$dir] = ['status' => $status, 'message' => $message];
        }
        return $results;
    }

    private function checkEnvFile(): array
    {
        $path = __DIR__ . '/../../.env';
        $status = file_exists($path);
        return [
            'status' => $status,
            'message' => $status ? '.env файл знайдено' : '.env файл не знайдено. Скопіюйте `.env.example` в `.env` та налаштуйте його.',
        ];
    }
    
    private function loadDbConfigFromEnv(): array
    {
        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->safeLoad();
        }
        return [
            'host' => $_ENV['DB_HOST'] ?? null,
            'port' => $_ENV['DB_PORT'] ?? null,
            'database' => $_ENV['DB_DATABASE'] ?? null,
            'username' => $_ENV['DB_USERNAME'] ?? null,
            'password' => $_ENV['DB_PASSWORD'] ?? null,
        ];
    }

    private function checkDbConnection(array $config): array
    {
        if (empty($config['host'])) {
            return ['status' => false, 'message' => 'Параметри БД не задані в .env файлі.'];
        }
        try {
            $dsn = sprintf('mysql:host=%s;port=%s', $config['host'], $config['port']);
            new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            return ['status' => true, 'message' => 'Підключення до сервера БД успішне.'];
        } catch (\Throwable $e) {
            return ['status' => false, 'message' => 'Не вдалося підключитись до сервера БД. Перевірте `DB_*` параметри в .env. Помилка: ' . $e->getMessage()];
        }
    }

    private function checkMigrationStatus(): array
    {
        $output = [];
        $returnVar = 0;
        $phinxPath = realpath(__DIR__ . '/../../vendor/bin/phinx');
        $configFile = realpath(__DIR__ . '/../../phinx.php');
        
        if (!$phinxPath || !$configFile) {
            return ['status' => false, 'message' => 'Phinx не знайдено. Виконайте `composer install`.'];
        }

        $fullCommand = sprintf('php %s status -c %s 2>&1', $phinxPath, $configFile);
        exec($fullCommand, $output, $returnVar);
        
        $outputString = implode("\n", $output);
        $hasMissing = str_contains($outputString, ' down ');

        if ($returnVar !== 0 && !str_contains($outputString, 'Could not connect to the database')) {
             return ['status' => false, 'message' => "Помилка виконання `phinx status`: " . $outputString];
        }

        if ($hasMissing) {
            return ['status' => false, 'message' => 'Є незастосовані міграції. Виконайте `composer db:migrate`.'];
        }
        
        return ['status' => true, 'message' => 'Всі міграції застосовані.'];
    }

    private function checkSeedingStatus(array $config): array
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['database']);
            $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `roles`");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['count'] > 0) {
                 return ['status' => true, 'message' => 'База даних містить початкові дані (ролі).'];
            } else {
                 return ['status' => false, 'message' => 'База даних порожня. Виконайте `composer db:seed` для наповнення.'];
            }
        } catch (\Throwable $e) {
            // This can happen if database doesn't exist yet, which is fine. Migrations check handles it.
            return ['status' => false, 'message' => 'База даних порожня або не існує. Виконайте `composer db:migrate` та `composer db:seed`.'];
        }
    }
}

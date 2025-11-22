<?php

namespace App\Controller;

use App\Core\View;
use PDO;

class InstallController
{
    public function form(): void
    {
        $feedback = $_SESSION['install_feedback'] ?? ['errors' => [], 'success' => false, 'details' => []];
        unset($_SESSION['install_feedback']);

        $step = $this->resolveStep();
        $defaults = $this->loadEnvDefaults();
        if (!empty($_SESSION['install_input'])) {
            $defaults = array_merge($defaults, $_SESSION['install_input']);
        }

        View::render('install/index.html.twig', [
            'defaults' => $defaults,
            'feedback' => $feedback,
            'step' => $step,
        ]);
    }

    public function install(): void
    {
        $step = (int)($_POST['step'] ?? 1);

        // Allow going back
        if (isset($_POST['back'])) {
            $this->redirectWithStep(max(1, $step - 1));
            return;
        }

        $input = [
            'step' => $step,
            'app_env' => $this->clean($_POST['app_env'] ?? 'dev'),
            'app_debug' => isset($_POST['app_debug']) ? 'true' : 'false',
            'db_connection' => $this->clean($_POST['db_connection'] ?? 'mysql'),
            'db_host' => $this->clean($_POST['db_host'] ?? '127.0.0.1'),
            'db_port' => $this->clean($_POST['db_port'] ?? '3306'),
            'db_database' => $this->clean($_POST['db_database'] ?? 'clinic'),
            'db_username' => $this->clean($_POST['db_username'] ?? 'root'),
            'db_password' => $_POST['db_password'] ?? '',
            'admin_email' => $this->clean($_POST['admin_email'] ?? ''),
            'admin_password' => $_POST['admin_password'] ?? '',
            'admin_first_name' => $this->clean($_POST['admin_first_name'] ?? 'Адмін'),
            'admin_last_name' => $this->clean($_POST['admin_last_name'] ?? 'Адміненко'),
            'mail_host' => $this->clean($_POST['mail_host'] ?? 'localhost'),
            'mail_port' => $this->clean($_POST['mail_port'] ?? '1025'),
            'mail_username' => $this->clean($_POST['mail_username'] ?? ''),
            'mail_password' => $_POST['mail_password'] ?? '',
            'mail_encryption' => $this->clean($_POST['mail_encryption'] ?? ''),
            'seed' => isset($_POST['seed']),
        ];
        // Merge previously stored input to avoid losing values between steps
        if (!empty($_SESSION['install_input'])) {
            $input = array_merge($_SESSION['install_input'], $input);
        }
        // Persist current input snapshot
        $this->storeInput($input);

        $errors = $this->validate($input);
        if (!empty($errors)) {
            $this->setFeedback(['errors' => $errors, 'success' => false]);
            $this->redirectWithStep($step);
            return;
        }

        // Step-specific actions
        if ($step === 2 && isset($_POST['test_db'])) {
            $this->handleDbTest($input);
            return;
        }

        if ($step < 3) {
            $this->storeInput($input);
            $this->redirectWithStep($step + 1);
            return;
        }

        $this->handleInstall($input);
    }

    private function clean(string $value): string
    {
        return trim($value);
    }

    private function validate(array $input): array
    {
        $errors = [];

        if ($input['step'] >= 2) {
            foreach (['db_host', 'db_port', 'db_database', 'db_username'] as $field) {
                if ($input[$field] === '') {
                    $errors[] = 'Поле ' . $field . ' обов\'язкове для MySQL.';
                }
            }
        }

        if ($input['step'] >= 3) {
            if ($input['admin_email'] === '' || !filter_var($input['admin_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Вкажіть коректний email адміністратора.';
            }
            if (strlen($input['admin_password']) < 8) {
                $errors[] = 'Пароль адміністратора має містити щонайменше 8 символів.';
            }
        }

        return $errors;
    }

    private function writeEnv(array $input): void
    {
        $lines = [
            'APP_ENV=' . ($input['app_env'] ?: 'prod'),
            'APP_DEBUG=' . $input['app_debug'],
            'APP_INSTALLED=true',
            'DB_CONNECTION=mysql',
            'DB_HOST=' . $input['db_host'],
            'DB_PORT=' . $input['db_port'],
            'DB_DATABASE=' . $input['db_database'],
            'DB_USERNAME=' . $input['db_username'],
            'DB_PASSWORD=' . $input['db_password'],
            'MAIL_MAILER=smtp',
            'MAIL_HOST=' . $input['mail_host'],
            'MAIL_PORT=' . $input['mail_port'],
            'MAIL_USERNAME=' . $input['mail_username'],
            'MAIL_PASSWORD=' . $input['mail_password'],
            'MAIL_ENCRYPTION=' . $input['mail_encryption'],
            'ADMIN_EMAIL=' . $input['admin_email'],
            'ADMIN_FIRST_NAME=' . $input['admin_first_name'],
            'ADMIN_LAST_NAME=' . $input['admin_last_name'],
        ];

        $envPath = $this->envPath();

        if (file_put_contents($envPath, implode(PHP_EOL, $lines) . PHP_EOL) === false) {
            throw new \RuntimeException('Не вдалося записати файл .env');
        }
    }

    private function createPdo(array $input, bool $withDb = true): PDO
    {
        if ($withDb) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $input['db_host'],
                $input['db_port'],
                $input['db_database']
            );
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;charset=utf8mb4',
                $input['db_host'],
                $input['db_port']
            );
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
            $options[\PDO::MYSQL_ATTR_MULTI_STATEMENTS] = true;
        }

        return new PDO($dsn, $input['db_username'], $input['db_password'], $options);
    }

    private function dropAndCreateDatabase(array $input): void
    {
        $pdo = $this->createPdo($input, false);
        $dbName = $input['db_database'];
        $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
        $pdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    private function createAdmin(PDO $pdo, array $input): void
    {
        $email = $input['admin_email'];
        $first = $input['admin_first_name'];
        $last = $input['admin_last_name'];
        $passwordHash = password_hash($input['admin_password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, first_name, last_name, role_id) VALUES (:username, :password_hash, :email, :first_name, :last_name, :role_id)
            ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), first_name = VALUES(first_name), last_name = VALUES(last_name)");
        $stmt->execute([
            ':username' => $email,
            ':password_hash' => $passwordHash,
            ':email' => $email,
            ':first_name' => $first,
            ':last_name' => $last,
            ':role_id' => 1,
        ]);
    }

    private function handleDbTest(array $input): void
    {
        try {
            $pdo = $this->createPdo($input);
            $pdo->query('SELECT 1');
            $this->setFeedback([
                'success' => false,
                'errors' => [],
                'details' => ['info' => 'Підключення успішне. Продовжуйте до наступного кроку.'],
            ]);
            $this->storeInput($input);
        } catch (\Throwable $e) {
            $this->setFeedback(['success' => false, 'errors' => ['Не вдалося підключитись: ' . $e->getMessage()]]);
        }
        $this->redirectWithStep(2);
    }

    private function handleInstall(array $input): void
    {
        try {
            // Write env first
            $this->writeEnv($input);

            // Drop and recreate DB
            $this->dropAndCreateDatabase($input);

            // Apply migrations
            $pdo = $this->createPdo($input);
            $this->runSqlDirectory($pdo, __DIR__ . '/../../database/migrations');

            // Seed if requested
            if ($input['seed']) {
                $this->runSqlDirectory($pdo, __DIR__ . '/../../database/seeds');
            }

            // Ensure admin exists/updated with provided credentials
            $this->createAdmin($pdo, $input);

            $this->setFeedback([
                'errors' => [],
                'success' => true,
                'details' => [
                    'db' => $this->dsnSummary($input),
                    'seeded' => $input['seed'],
                    'admin_email' => $input['admin_email'],
                ],
            ]);
            unset($_SESSION['install_input']);
        } catch (\Throwable $e) {
            $this->setFeedback([
                'errors' => ['Встановлення перервано: ' . $e->getMessage()],
                'success' => false,
            ]);
        }

        $this->redirectWithStep(3);
    }

    private function runSqlDirectory(PDO $pdo, string $path): void
    {
        $files = glob($path . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            $sql = file_get_contents($file);
            foreach ($this->splitStatements($sql) as $statement) {
                if ($statement === '') {
                    continue;
                }
                $pdo->exec($statement);
            }
        }
    }

    private function splitStatements(string $sql): array
    {
        $parts = array_map('trim', explode(';', $sql));

        return array_values(array_filter($parts, static fn($part) => $part !== ''));
    }

    private function loadEnvDefaults(): array
    {
        $envPath = $this->envPath();
        $defaults = [
            'APP_ENV' => 'dev',
            'APP_DEBUG' => 'true',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'clinic',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
            'MAIL_HOST' => 'localhost',
            'MAIL_PORT' => '1025',
            'MAIL_USERNAME' => '',
            'MAIL_PASSWORD' => '',
            'MAIL_ENCRYPTION' => '',
            'ADMIN_EMAIL' => 'admin@clinic.ua',
            'ADMIN_FIRST_NAME' => 'Адмін',
            'ADMIN_LAST_NAME' => 'Адміненко',
        ];

        if (!file_exists($envPath)) {
            return $defaults;
        }

        $content = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($content as $line) {
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $defaults[$key] = $value;
        }

        if (($defaults['DB_CONNECTION'] ?? '') !== 'mysql') {
            $defaults['DB_CONNECTION'] = 'mysql';
            $defaults['DB_DATABASE'] = 'clinic';
        }
        if (str_contains($defaults['DB_DATABASE'] ?? '', '.sqlite')) {
            $defaults['DB_DATABASE'] = 'clinic';
        }

        return $defaults;
    }

    private function envPath(): string
    {
        return __DIR__ . '/../../.env';
    }

    private function setFeedback(array $payload): void
    {
        $_SESSION['install_feedback'] = array_merge(['errors' => [], 'success' => false, 'details' => []], $payload);
    }

    private function storeInput(array $input): void
    {
        $_SESSION['install_input'] = $input;
    }

    private function dsnSummary(array $input): string
    {
        return sprintf(
            'MySQL — %s:%s/%s',
            $input['db_host'],
            $input['db_port'],
            $input['db_database']
        );
    }

    private function resolveStep(): int
    {
        $step = (int)($_GET['step'] ?? 1);
        return max(1, min(3, $step));
    }

    private function redirectWithStep(int $step): void
    {
        header('Location: /install?step=' . max(1, min(3, $step)));
        exit;
    }
}

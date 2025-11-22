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

        $defaults = $this->loadEnvDefaults();

        View::render('install/index.html.twig', [
            'defaults' => $defaults,
            'feedback' => $feedback,
        ]);
    }

    public function install(): void
    {
        $input = [
            'app_env' => $this->clean($_POST['app_env'] ?? 'dev'),
            'app_debug' => isset($_POST['app_debug']) ? 'true' : 'false',
            'db_connection' => $this->clean($_POST['db_connection'] ?? 'mysql'),
            'db_host' => $this->clean($_POST['db_host'] ?? '127.0.0.1'),
            'db_port' => $this->clean($_POST['db_port'] ?? '3306'),
            'db_database' => $this->clean($_POST['db_database'] ?? 'clinic'),
            'db_username' => $this->clean($_POST['db_username'] ?? 'root'),
            'db_password' => $_POST['db_password'] ?? '',
            'mail_host' => $this->clean($_POST['mail_host'] ?? 'localhost'),
            'mail_port' => $this->clean($_POST['mail_port'] ?? '1025'),
            'mail_username' => $this->clean($_POST['mail_username'] ?? ''),
            'mail_password' => $_POST['mail_password'] ?? '',
            'mail_encryption' => $this->clean($_POST['mail_encryption'] ?? ''),
            'seed' => isset($_POST['seed']),
        ];

        $errors = $this->validate($input);

        if (!empty($errors)) {
            $this->setFeedback(['errors' => $errors, 'success' => false]);
            header('Location: /install');
            return;
        }

        try {
            $this->writeEnv($input);
            $pdo = $this->createPdo($input);
            $this->runSqlDirectory($pdo, __DIR__ . '/../../database/migrations');

            if ($input['seed']) {
                $this->runSqlDirectory($pdo, __DIR__ . '/../../database/seeds');
            }

            $this->setFeedback([
                'errors' => [],
                'success' => true,
                'details' => [
                    'db' => $this->dsnSummary($input),
                    'seeded' => $input['seed'],
                    'default_admin' => 'admin / password',
                ],
            ]);
        } catch (\Throwable $e) {
            $this->setFeedback([
                'errors' => ['Встановлення перервано: ' . $e->getMessage()],
                'success' => false,
            ]);
        }

        header('Location: /install');
    }

    private function clean(string $value): string
    {
        return trim($value);
    }

    private function validate(array $input): array
    {
        $errors = [];

        foreach (['db_host', 'db_port', 'db_database', 'db_username'] as $field) {
            if ($input[$field] === '') {
                $errors[] = 'Поле ' . $field . ' обов\'язкове для MySQL.';
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
        ];

        $envPath = $this->envPath();

        if (file_put_contents($envPath, implode(PHP_EOL, $lines) . PHP_EOL) === false) {
            throw new \RuntimeException('Не вдалося записати файл .env');
        }
    }

    private function createPdo(array $input): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $input['db_host'],
            $input['db_port'],
            $input['db_database']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
            $options[\PDO::MYSQL_ATTR_MULTI_STATEMENTS] = true;
        }

        return new PDO($dsn, $input['db_username'], $input['db_password'], $options);
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
            'DB_CONNECTION' => 'mysql',
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

    private function dsnSummary(array $input): string
    {
        return sprintf(
            'MySQL — %s:%s/%s',
            $input['db_host'],
            $input['db_port'],
            $input['db_database']
        );
    }
}

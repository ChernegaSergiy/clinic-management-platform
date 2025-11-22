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
            $isSqlite = $input['db_connection'] === 'sqlite';
            $this->runSqlDirectory($pdo, __DIR__ . '/../../database/migrations', $isSqlite);

            if ($input['seed']) {
                $this->runSqlDirectory($pdo, __DIR__ . '/../../database/seeds', $isSqlite);
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

        if ($input['db_connection'] === 'mysql') {
            foreach (['db_host', 'db_port', 'db_database', 'db_username'] as $field) {
                if ($input[$field] === '') {
                    $errors[] = 'Поле ' . $field . ' обов\'язкове для MySQL.';
                }
            }
        } elseif ($input['db_connection'] === 'sqlite') {
            if ($input['db_database'] === '') {
                $errors[] = 'Назва файлу бази для SQLite є обов\'язковою.';
            }
        } else {
            $errors[] = 'Невідомий тип бази даних.';
        }

        return $errors;
    }

    private function writeEnv(array $input): void
    {
        $lines = [
            'APP_ENV=' . ($input['app_env'] ?: 'prod'),
            'APP_DEBUG=' . $input['app_debug'],
            'APP_INSTALLED=true',
            'DB_CONNECTION=' . $input['db_connection'],
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
        if ($input['db_connection'] === 'sqlite') {
            $dsn = 'sqlite:' . __DIR__ . '/../../database/' . $input['db_database'];
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } else {
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

            $pdo = new PDO($dsn, $input['db_username'], $input['db_password'], $options);
        }

        return $pdo;
    }

    private function runSqlDirectory(PDO $pdo, string $path, bool $isSqlite = false): void
    {
        $files = glob($path . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            $sql = file_get_contents($file);
            if ($isSqlite) {
                $sql = $this->normalizeForSqlite($sql);
            }
            foreach ($this->splitStatements($sql) as $statement) {
                if ($statement === '') {
                    continue;
                }
                $pdo->exec($statement);
            }
        }
    }

    private function normalizeForSqlite(string $sql): string
    {
        // Прибрати MySQL-специфічні конструкції для сумісності з SQLite
        $patterns = [
            '/INT\\s+AUTO_INCREMENT\\s+PRIMARY\\s+KEY/i' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            '/INT\\s+AUTO_INCREMENT/i' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            '/TIMESTAMP\\s+DEFAULT\\s+CURRENT_TIMESTAMP\\s+ON\\s+UPDATE\\s+CURRENT_TIMESTAMP/i' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            '/DEFAULT\\s+CURRENT_TIMESTAMP\\s+ON\\s+UPDATE\\s+CURRENT_TIMESTAMP/i' => 'DEFAULT CURRENT_TIMESTAMP',
            '/ON\\s+UPDATE\\s+CURRENT_TIMESTAMP/i' => '',
            '/ENGINE=InnoDB.*;/i' => ';',
            '/ENUM\\(([^)]*)\\)/i' => 'TEXT',
            '/\\bUNSIGNED\\b/i' => '',
        ];

        $sql = preg_replace(array_keys($patterns), array_values($patterns), $sql) ?? $sql;

        // Видаляємо FULLTEXT-індекси, яких немає у SQLite
        $lines = explode("\n", $sql);
        $filtered = array_filter($lines, static function ($line) {
            return stripos($line, 'FULLTEXT') === false;
        });

        $normalized = implode("\n", $filtered);

        // SQLite не підтримує ALTER COLUMN SET DEFAULT — прибираємо такі рядки
        $normalized = preg_replace('/ALTER\\s+TABLE\\s+[^;]+ALTER\\s+COLUMN[^;]+;/i', '', $normalized) ?? $normalized;

        return $normalized;
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

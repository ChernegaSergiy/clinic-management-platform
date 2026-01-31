<?php

namespace App\Database;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $dbName = $_ENV['DB_DATABASE'] ?? 'clinic';
            $dbUser = $_ENV['DB_USERNAME'] ?? 'root';
            $dbPass = $_ENV['DB_PASSWORD'] ?? '';
            $dbPort = $_ENV['DB_PORT'] ?? '3306';
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                $msg = sprintf("Не вдалося підключитися до бази даних (%s@%s:%s/%s): %s\n", $dbUser, $dbHost, $dbPort, $dbName, $e->getMessage());
                $msg .= "Перевірте, чи запущено сервер БД і чи вірні змінні оточення: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD.";
                throw new \RuntimeException($msg, 0, $e);
            }
        }

        return self::$instance;
    }
}

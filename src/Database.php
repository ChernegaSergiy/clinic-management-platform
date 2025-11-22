<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // Завантаження конфігурації з .env (поки що спрощено)
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
                die("Помилка підключення до бази даних: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}

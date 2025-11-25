<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

return [
    'paths' => [
        'migrations' => 'database/migrations_phinx',
        'seeds' => 'database/seeds_phinx'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'production',
        'production' => [
            'adapter' => getenv('DB_CONNECTION') ?: 'mysql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'name' => getenv('DB_DATABASE') ?: 'clinic',
            'user' => getenv('DB_USERNAME') ?: 'root',
            'pass' => getenv('DB_PASSWORD') ?: '',
            'port' => getenv('DB_PORT') ?: 3306,
            'charset' => 'utf8',
        ],
        'development' => [
            'adapter' => 'sqlite',
            'name' => './clinic_dev.sqlite'
        ]
    ],
    'version_order' => 'creation'
];

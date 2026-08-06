<?php

namespace App\Infrastructure\Database;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class DoctrineFactory
{
    public static function createEntityManager() : EntityManager
    {
        $projectDir = dirname(__DIR__, 3);
        $paths = [$projectDir . '/src/Entity'];
        $isDevMode = ($_ENV['APP_DEBUG'] ?? 'true') === 'true';

        $cacheDir = $projectDir . '/var/cache/doctrine';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        $cache = clone new FilesystemAdapter('doctrine', 0, $cacheDir);

        $config = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode, $cacheDir, $cache);

        $dbParams = [
            'driver'   => 'pdo_mysql',
            'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port'     => $_ENV['DB_PORT'] ?? 3306,
            'dbname'   => $_ENV['DB_DATABASE'] ?? 'clinic',
            'user'     => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset'  => 'utf8mb4',
        ];

        $connection = DriverManager::getConnection($dbParams, $config);

        return new EntityManager($connection, $config);
    }
}

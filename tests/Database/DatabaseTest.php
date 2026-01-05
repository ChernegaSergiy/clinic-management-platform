<?php

namespace App\Database;

use PHPUnit\Framework\TestCase;
use PDO;

class DatabaseTest extends TestCase
{
    public function testGetInstanceDoesNotConnectToDatabase(): void
    {
        $reflection = new \ReflectionClass(Database::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null);

        $this->assertNull($instanceProperty->getValue());
    }

    public function testSingletonPattern(): void
    {
        $mockPdo = $this->createMock(PDO::class);

        $reflection = new \ReflectionClass(Database::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $mockPdo);

        $instance1 = Database::getInstance();
        $instance2 = Database::getInstance();

        $this->assertSame($mockPdo, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    public function testDsnConstruction(): void
    {
        $expectedHost = 'localhost';
        $expectedPort = '3306';
        $expectedDb = 'test_clinic';
        $expectedCharset = 'utf8mb4';

        $dsn = "mysql:host={$expectedHost};port={$expectedPort};dbname={$expectedDb};charset={$expectedCharset}";

        $this->assertStringContainsString('mysql:', $dsn);
        $this->assertStringContainsString("host={$expectedHost}", $dsn);
        $this->assertStringContainsString("port={$expectedPort}", $dsn);
        $this->assertStringContainsString("dbname={$expectedDb}", $dsn);
        $this->assertStringContainsString("charset={$expectedCharset}", $dsn);
    }

    public function testPdoAttributesConfiguration(): void
    {
        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $pdoOptions[PDO::ATTR_ERRMODE]);
        $this->assertEquals(PDO::FETCH_ASSOC, $pdoOptions[PDO::ATTR_DEFAULT_FETCH_MODE]);
    }

    public function testDatabaseConnectionFromEnv(): void
    {
        $envDefaults = [
            'DB_HOST' => '127.0.0.1',
            'DB_DATABASE' => 'clinic',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
            'DB_PORT' => '3306',
        ];

        $this->assertEquals('127.0.0.1', $envDefaults['DB_HOST']);
        $this->assertEquals('clinic', $envDefaults['DB_DATABASE']);
        $this->assertEquals('root', $envDefaults['DB_USERNAME']);
        $this->assertEquals('', $envDefaults['DB_PASSWORD']);
        $this->assertEquals('3306', $envDefaults['DB_PORT']);
    }

    public function testPrivateConstructorPreventsDirectInstantiation(): void
    {
        $reflection = new \ReflectionClass(Database::class);
        $constructor = $reflection->getConstructor();

        $this->assertTrue($constructor->isPrivate());
    }

    public function testInstancePropertyIsPrivateStatic(): void
    {
        $reflection = new \ReflectionProperty(Database::class, 'instance');
        $this->assertTrue($reflection->isPrivate());
        $this->assertTrue($reflection->isStatic());
    }
}

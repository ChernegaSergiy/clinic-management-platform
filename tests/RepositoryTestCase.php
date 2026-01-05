<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use PDO;

abstract class RepositoryTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = $this->createMockPdo();
    }

    private function createMockPdo(): PDO
    {
        $pdo = $this->createMock(PDO::class);

        $pdo->method('prepare')
            ->willReturnCallback(function ($sql) {
                $stmt = $this->createMock(\PDOStatement::class);
                return $stmt;
            });

        return $pdo;
    }
}

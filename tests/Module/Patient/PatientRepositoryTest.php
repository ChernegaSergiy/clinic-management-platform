<?php

namespace App\Module\Patient\Repository;

use PHPUnit\Framework\TestCase;
use PDO;

class PatientRepositoryTest extends TestCase
{
    private PatientRepository $repository;
    private PDO $mockPdo;
    private \PDOStatement $mockStmt;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStmt = $this->createMock(\PDOStatement::class);
        $this->mockPdo->method('prepare')->willReturn($this->mockStmt);
        $this->mockPdo->method('query')->willReturn($this->mockStmt);

        $reflection = new \ReflectionClass(PatientRepository::class);
        $repository = $reflection->newInstanceWithoutConstructor();
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($repository, $this->mockPdo);
        $this->repository = $repository;
    }

    public function testFindAllReturnsEmptyArrayWhenNoPatients(): void
    {
        $this->mockStmt->method('fetchAll')->willReturn([]);
        $result = $this->repository->findAll();
        $this->assertEmpty($result);
    }

    public function testFindAllReturnsPatients(): void
    {
        $expected = [
            ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
            ['id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->findAll();
        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]['first_name']);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $this->mockStmt->method('fetch')->willReturn(false);
        $result = $this->repository->findById(999);
        $this->assertNull($result);
    }

    public function testFindByIdReturnsPatient(): void
    {
        $expected = ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'];
        $this->mockStmt->method('fetch')->willReturn($expected);
        $result = $this->repository->findById(1);
        $this->assertEquals($expected, $result);
    }

    public function testFindByCredentialsReturnsNullWhenNotFound(): void
    {
        $this->mockStmt->method('fetch')->willReturn(false);
        $result = $this->repository->findByCredentials('Doe', 'John', '1990-01-01');
        $this->assertNull($result);
    }

    public function testFindByCredentialsReturnsPatient(): void
    {
        $expected = ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'];
        $this->mockStmt->method('fetch')->willReturn($expected);
        $result = $this->repository->findByCredentials('Doe', 'John', '1990-01-01');
        $this->assertEquals($expected, $result);
    }

    public function testFindByTaxIdReturnsNullWhenNotFound(): void
    {
        $this->mockStmt->method('fetch')->willReturn(false);
        $result = $this->repository->findByTaxId('TAX123');
        $this->assertNull($result);
    }

    public function testFindByTaxIdReturnsPatient(): void
    {
        $expected = ['id' => 1, 'tax_id' => 'TAX123'];
        $this->mockStmt->method('fetch')->willReturn($expected);
        $result = $this->repository->findByTaxId('TAX123');
        $this->assertEquals($expected, $result);
    }

    public function testCountAllReturnsCount(): void
    {
        $this->mockStmt->method('fetchColumn')->willReturn(42);
        $result = $this->repository->countAll();
        $this->assertEquals(42, $result);
    }

    public function testFindByIdsReturnsEmptyForEmptyArray(): void
    {
        $result = $this->repository->findByIds([]);
        $this->assertEmpty($result);
    }

    public function testFindByIdsReturnsPatients(): void
    {
        $expected = [
            ['id' => 1, 'first_name' => 'John'],
            ['id' => 2, 'first_name' => 'Jane']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->findByIds([1, 2]);
        $this->assertCount(2, $result);
    }

    public function testFindAllActiveReturnsActivePatients(): void
    {
        $expected = [
            ['id' => 1, 'full_name' => 'Doe John'],
            ['id' => 2, 'full_name' => 'Smith Jane']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->findAllActive();
        $this->assertCount(2, $result);
    }
}

<?php

namespace App\Module\MedicalRecord\Repository;

use PHPUnit\Framework\TestCase;
use PDO;

class MedicalRecordRepositoryTest extends TestCase
{
    private MedicalRecordRepository $repository;
    private PDO $mockPdo;
    private \PDOStatement $mockStmt;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStmt = $this->createMock(\PDOStatement::class);
        $this->mockPdo->method('prepare')->willReturn($this->mockStmt);

        $reflection = new \ReflectionClass(MedicalRecordRepository::class);
        $repository = $reflection->newInstanceWithoutConstructor();
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($repository, $this->mockPdo);
        $this->repository = $repository;
    }

    public function testFindByPatientIdReturnsRecords(): void
    {
        $expected = [
            ['id' => 1, 'diagnosis_text' => 'Checkup'],
            ['id' => 2, 'diagnosis_text' => 'Follow-up']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->findByPatientId(1);
        $this->assertCount(2, $result);
    }

    public function testFindByDoctorIdReturnsRecords(): void
    {
        $expected = [
            ['id' => 1, 'patient_name' => 'John Doe'],
            ['id' => 2, 'patient_name' => 'Jane Smith']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->findByDoctorId(1);
        $this->assertCount(2, $result);
    }

    public function testFindAllReturnsRecords(): void
    {
        $expected = [
            ['id' => 1, 'diagnosis_text' => 'Checkup'],
            ['id' => 2, 'diagnosis_text' => 'Follow-up']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->findAll();
        $this->assertCount(2, $result);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $this->mockStmt->method('fetch')->willReturn(false);
        $this->mockStmt->method('fetchAll')->willReturn([]);
        $result = $this->repository->findById(999);
        $this->assertNull($result);
    }

    public function testFindByIdReturnsRecordWithIcdCodes(): void
    {
        $expectedRecord = ['id' => 1, 'diagnosis_text' => 'Checkup'];
        $expectedIcdCodes = [
            ['id' => 1, 'code' => 'A01', 'description' => 'Typhoid fever']
        ];
        $this->mockStmt->method('fetch')->willReturnOnConsecutiveCalls($expectedRecord, false);
        $this->mockStmt->method('fetchAll')->willReturnOnConsecutiveCalls($expectedIcdCodes, []);
        $result = $this->repository->findById(1);
        $this->assertEquals($expectedRecord['id'], $result['id']);
        $this->assertEquals($expectedRecord['diagnosis_text'], $result['diagnosis_text']);
        $this->assertArrayHasKey('icd_codes', $result);
        $this->assertArrayHasKey('intervention_codes', $result);
    }

    public function testAttachIcdCodesFiltersInvalidIds(): void
    {
        $this->mockStmt->method('execute')->willReturn(true);
        $result = $this->repository->attachIcdCodes(1, [1, 2, 0, -1, 'abc', 3]);
        $this->assertTrue($result);
    }

    public function testAttachIcdCodesWithEmptyArray(): void
    {
        $result = $this->repository->attachIcdCodes(1, []);
        $this->assertTrue($result);
    }

    public function testGetIcdCodesForMedicalRecord(): void
    {
        $expected = [
            ['id' => 1, 'code' => 'A01', 'description' => 'Typhoid fever'],
            ['id' => 2, 'code' => 'A02', 'description' => 'Other salmonella infections']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->getIcdCodesForMedicalRecord(1);
        $this->assertCount(2, $result);
    }

    public function testGetInterventionCodesForMedicalRecord(): void
    {
        $expected = [
            ['id' => 1, 'code' => 'T81.0', 'description' => 'Drainage of wound']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->getInterventionCodesForMedicalRecord(1);
        $this->assertCount(1, $result);
    }

    public function testAttachInterventionCodesFiltersInvalidIds(): void
    {
        $this->mockStmt->method('execute')->willReturn(true);
        $result = $this->repository->attachInterventionCodes(1, [1, 'invalid', 0, 2]);
        $this->assertTrue($result);
    }

    public function testAttachInterventionCodesWithEmptyArray(): void
    {
        $result = $this->repository->attachInterventionCodes(1, []);
        $this->assertTrue($result);
    }
}

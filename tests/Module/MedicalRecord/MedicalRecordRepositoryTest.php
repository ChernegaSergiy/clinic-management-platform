<?php

namespace App\Module\MedicalRecord\Repository;

use App\Entity\MedicalRecord;
use App\Tests\RepositoryTestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MedicalRecordRepositoryTest extends RepositoryTestCase
{
    private MedicalRecordRepository $repository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private Connection&MockObject $mockConnection;

    protected function setUp() : void
    {
        parent::setUp();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $registry = $this->createMockManagerRegistry(MedicalRecord::class);

        $this->mockConnection = $this->createMock(Connection::class);
        $this->entityManager->method('getConnection')->willReturn($this->mockConnection);

        $this->repository = new MedicalRecordRepository($registry, $this->eventDispatcher);
    }

    public function testFindByPatientIdReturnsRecords() : void
    {
        $expected = [
            ['id' => 1, 'diagnosis_text' => 'Checkup'],
            ['id' => 2, 'diagnosis_text' => 'Follow-up']
        ];
        $mockQueryBuilder = $this->createMockQueryBuilder($expected);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findByPatientId(1);
        $this->assertCount(2, $result);
    }

    public function testFindByDoctorIdReturnsRecords() : void
    {
        $expected = [
            ['id' => 1, 'patient_name' => 'John Doe'],
            ['id' => 2, 'patient_name' => 'Jane Smith']
        ];
        $mockQueryBuilder = $this->createMockQueryBuilder($expected);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findByDoctorId(1);
        $this->assertCount(2, $result);
    }

    public function testFindAllReturnsRecords() : void
    {
        $expected = [
            ['id' => 1, 'diagnosis_text' => 'Checkup'],
            ['id' => 2, 'diagnosis_text' => 'Follow-up']
        ];
        $mockQueryBuilder = $this->createMockQueryBuilder($expected);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findAll();
        $this->assertCount(2, $result);
    }

    public function testFindByIdReturnsNullWhenNotFound() : void
    {
        $mockQueryBuilder = $this->createMockQueryBuilder(null, true);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findById(999);
        $this->assertNull($result);
    }

    public function testFindByIdReturnsRecordWithIcdCodes() : void
    {
        $expectedRecord = ['id' => 1, 'diagnosis_text' => 'Checkup'];
        $expectedIcdCodes = [
            ['id' => 1, 'code' => 'A01', 'description' => 'Typhoid fever']
        ];

        $mockQueryBuilder = $this->createMockQueryBuilder($expectedRecord, true);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $this->mockConnection->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls($expectedIcdCodes, []);

        $result = $this->repository->findById(1);
        $this->assertEquals($expectedRecord['id'], $result['id']);
        $this->assertEquals($expectedRecord['diagnosis_text'], $result['diagnosis_text']);
        $this->assertArrayHasKey('icd_codes', $result);
        $this->assertArrayHasKey('intervention_codes', $result);
    }

    public function testAttachIcdCodesFiltersInvalidIds() : void
    {
        $this->mockConnection->method('executeStatement')->willReturn(1);
        $result = $this->repository->attachIcdCodes(1, [1, 2, 0, -1, 'abc', 3]);
        $this->assertTrue($result);
    }

    public function testAttachIcdCodesWithEmptyArray() : void
    {
        $result = $this->repository->attachIcdCodes(1, []);
        $this->assertTrue($result);
    }

    public function testGetIcdCodesForMedicalRecord() : void
    {
        $expected = [
            ['id' => 1, 'code' => 'A01', 'description' => 'Typhoid fever'],
            ['id' => 2, 'code' => 'A02', 'description' => 'Other salmonella infections']
        ];
        $this->mockConnection->method('fetchAllAssociative')->willReturn($expected);

        $result = $this->repository->getIcdCodesForMedicalRecord(1);
        $this->assertCount(2, $result);
    }

    public function testGetInterventionCodesForMedicalRecord() : void
    {
        $expected = [
            ['id' => 1, 'code' => 'T81.0', 'description' => 'Drainage of wound']
        ];
        $this->mockConnection->method('fetchAllAssociative')->willReturn($expected);

        $result = $this->repository->getInterventionCodesForMedicalRecord(1);
        $this->assertCount(1, $result);
    }

    public function testAttachInterventionCodesFiltersInvalidIds() : void
    {
        $this->mockConnection->method('executeStatement')->willReturn(1);
        $result = $this->repository->attachInterventionCodes(1, [1, 'invalid', 0, 2]);
        $this->assertTrue($result);
    }

    public function testAttachInterventionCodesWithEmptyArray() : void
    {
        $result = $this->repository->attachInterventionCodes(1, []);
        $this->assertTrue($result);
    }
}

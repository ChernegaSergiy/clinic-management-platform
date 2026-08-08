<?php

namespace App\Tests\Bundles\PatientBundle\Repository;

use App\Core\Service\AuditLogger;
use App\Entity\Patient;
use App\Tests\RepositoryTestCase;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PatientRepositoryTest extends RepositoryTestCase
{
    private PatientRepository $repository;
    private AuditLogger&MockObject $auditLogger;
    private EventDispatcherInterface&MockObject $eventDispatcher;

    protected function setUp() : void
    {
        parent::setUp();

        $this->auditLogger = $this->createMock(AuditLogger::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $registry = $this->createMockManagerRegistry(Patient::class);

        $this->repository = new PatientRepository($registry, $this->auditLogger, $this->eventDispatcher);
    }

    public function testFindAllReturnsEmptyArrayWhenNoPatients() : void
    {
        $mockQueryBuilder = $this->createMockQueryBuilder([]);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findAll();
        $this->assertEmpty($result);
    }

    public function testFindAllReturnsPatients() : void
    {
        $expected = [
            ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
            ['id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith']
        ];
        $mockQueryBuilder = $this->createMockQueryBuilder($expected);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findAll();
        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]['first_name']);
    }

    public function testFindByIdReturnsNullWhenNotFound() : void
    {
        $mockQueryBuilder = $this->createMockQueryBuilder(null, true);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findById(999);
        $this->assertNull($result);
    }

    public function testFindByIdReturnsPatient() : void
    {
        $expected = ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'];
        $mockQueryBuilder = $this->createMockQueryBuilder($expected, true);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findById(1);
        $this->assertEquals($expected, $result);
    }

    public function testFindByCredentialsReturnsNullWhenNotFound() : void
    {
        $mockQueryBuilder = $this->createMockQueryBuilder(null, true);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findByCredentials('Doe', 'John', '1990-01-01');
        $this->assertNull($result);
    }

    public function testFindByCredentialsReturnsPatient() : void
    {
        $expected = ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'];
        $mockQueryBuilder = $this->createMockQueryBuilder($expected, true);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findByCredentials('Doe', 'John', '1990-01-01');
        $this->assertEquals($expected, $result);
    }

    public function testFindByTaxIdReturnsNullWhenNotFound() : void
    {
        $mockQueryBuilder = $this->createMockQueryBuilder(null, true);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findByTaxId('TAX123');
        $this->assertNull($result);
    }

    public function testFindByTaxIdReturnsPatient() : void
    {
        $expected = ['id' => 1, 'tax_id' => 'TAX123'];
        $mockQueryBuilder = $this->createMockQueryBuilder($expected, true);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findByTaxId('TAX123');
        $this->assertEquals($expected, $result);
    }

    public function testCountAllReturnsCount() : void
    {
        $persister = $this->createMock(EntityPersister::class);
        $persister->method('count')->willReturn(42);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->method('getEntityPersister')->willReturn($persister);

        $this->entityManager->method('getUnitOfWork')->willReturn($uow);

        $result = $this->repository->countAll();
        $this->assertEquals(42, $result);
    }

    public function testFindByIdsReturnsEmptyForEmptyArray() : void
    {
        $result = $this->repository->findByIds([]);
        $this->assertEmpty($result);
    }

    public function testFindByIdsReturnsPatients() : void
    {
        $expected = [
            ['id' => 1, 'first_name' => 'John'],
            ['id' => 2, 'first_name' => 'Jane']
        ];
        $mockQueryBuilder = $this->createMockQueryBuilder($expected);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findByIds([1, 2]);
        $this->assertCount(2, $result);
    }

    public function testFindAllActiveReturnsActivePatients() : void
    {
        $expected = [
            ['id' => 1, 'full_name' => 'Doe John'],
            ['id' => 2, 'full_name' => 'Smith Jane']
        ];
        $mockQueryBuilder = $this->createMockQueryBuilder($expected);
        $this->entityManager->method('createQueryBuilder')->willReturn($mockQueryBuilder);

        $result = $this->repository->findAllActive();
        $this->assertCount(2, $result);
    }
}

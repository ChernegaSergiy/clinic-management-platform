<?php

namespace App\Tests\Bundles\AppointmentBundle\Repository;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepository;
use App\Entity\Appointment;
use App\Tests\RepositoryTestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AppointmentRepositoryTest extends RepositoryTestCase
{
    private AppointmentRepository $repository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private Connection&MockObject $mockConnection;

    protected function setUp() : void
    {
        parent::setUp();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $registry = $this->createMockManagerRegistry(Appointment::class);

        $this->mockConnection = $this->createMock(Connection::class);
        $this->entityManager->method('getConnection')->willReturn($this->mockConnection);

        $this->repository = new AppointmentRepository($registry, $this->eventDispatcher);
    }

    public function testFindAllReturnsEmptyArrayWhenNoAppointments() : void
    {
        $this->createMockQueryBuilder([]);
        $result = $this->repository->findAll();
        $this->assertEmpty($result);
    }

    public function testFindAllReturnsAppointments() : void
    {
        $expected = [
            ['id' => 1, 'patient_name' => 'John Doe', 'doctor_name' => 'Dr. Smith'],
            ['id' => 2, 'patient_name' => 'Jane Smith', 'doctor_name' => 'Dr. Johnson']
        ];
        $this->createMockQueryBuilder($expected);
        $result = $this->repository->findAll();
        $this->assertCount(2, $result);
    }

    public function testFindByIdReturnsNullWhenNotFound() : void
    {
        $this->createMockQueryBuilder(null, true);
        $result = $this->repository->findById(999);
        $this->assertNull($result);
    }

    public function testFindByIdReturnsAppointment() : void
    {
        $expected = ['id' => 1, 'patient_name' => 'John Doe'];
        $this->createMockQueryBuilder($expected, true);
        $result = $this->repository->findById(1);
        $this->assertEquals($expected, $result);
    }

    public function testFindByPatientIdReturnsAppointments() : void
    {
        $expected = [
            ['id' => 1, 'start_time' => '2024-06-15 10:00:00'],
            ['id' => 2, 'start_time' => '2024-06-16 14:00:00']
        ];
        $this->createMockQueryBuilder($expected);
        $result = $this->repository->findByPatientId(1);
        $this->assertCount(2, $result);
    }

    public function testFindByDoctorIdReturnsAppointments() : void
    {
        $expected = [
            ['id' => 1, 'patient_name' => 'John Doe'],
            ['id' => 2, 'patient_name' => 'Jane Smith']
        ];
        $this->createMockQueryBuilder($expected);
        $result = $this->repository->findByDoctorId(1);
        $this->assertCount(2, $result);
    }

    public function testFindUpcomingReturnsAppointments() : void
    {
        $expected = [
            ['id' => 1, 'patient_name' => 'John Doe'],
            ['id' => 2, 'patient_name' => 'Jane Smith']
        ];
        $this->createMockQueryBuilder($expected);
        $result = $this->repository->findUpcoming();
        $this->assertCount(2, $result);
    }

    public function testIsPatientAssignedToDoctorReturnsTrue() : void
    {
        $this->createMockQueryBuilder(1, true);
        $result = $this->repository->isPatientAssignedToDoctor(1, 1);
        $this->assertTrue($result);
    }

    public function testIsPatientAssignedToDoctorReturnsFalse() : void
    {
        $this->createMockQueryBuilder(null, true);
        $result = $this->repository->isPatientAssignedToDoctor(1, 1);
        $this->assertFalse($result);
    }

    public function testIsAppointmentOwnedByDoctorReturnsTrue() : void
    {
        $this->createMockQueryBuilder(1, true);
        $result = $this->repository->isAppointmentOwnedByDoctor(1, 1);
        $this->assertTrue($result);
    }

    public function testGenerateWaitlistTicketFormat() : void
    {
        $this->createMockQueryBuilder(5, true);
        $result = $this->repository->generateWaitlistTicket();
        $this->assertStringStartsWith('WL-', $result);
        $this->assertStringContainsString('-00006', $result);
    }

    public function testCountScheduledByDate() : void
    {
        $this->createMockQueryBuilder(10, true);
        $result = $this->repository->countScheduledByDate('2024-06-15');
        $this->assertEquals(10, $result);
    }

    public function testCountAppointmentsByDate() : void
    {
        $this->createMockQueryBuilder(15, true);
        $result = $this->repository->countAppointmentsByDate('2024-06-15');
        $this->assertEquals(15, $result);
    }

    public function testFindByDateRange() : void
    {
        $expected = [
            ['id' => 1, 'start_time' => '2024-06-15 10:00:00'],
            ['id' => 2, 'start_time' => '2024-06-15 14:00:00']
        ];
        $this->createMockQueryBuilder($expected);
        $result = $this->repository->findByDateRange('2024-06-15 00:00:00', '2024-06-15 23:59:59');
        $this->assertCount(2, $result);
    }
}

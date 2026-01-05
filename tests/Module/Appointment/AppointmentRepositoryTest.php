<?php

namespace App\Module\Appointment\Repository;

use PHPUnit\Framework\TestCase;
use PDO;

class AppointmentRepositoryTest extends TestCase
{
    private AppointmentRepository $repository;
    private PDO $mockPdo;
    private \PDOStatement $mockStmt;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStmt = $this->createMock(\PDOStatement::class);
        $this->mockPdo->method('prepare')->willReturn($this->mockStmt);
        $this->mockPdo->method('query')->willReturn($this->mockStmt);

        $reflection = new \ReflectionClass(AppointmentRepository::class);
        $repository = $reflection->newInstanceWithoutConstructor();
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($repository, $this->mockPdo);
        $this->repository = $repository;
    }

    public function testFindAllReturnsEmptyArrayWhenNoAppointments(): void
    {
        $this->mockStmt->method('fetchAll')->willReturn([]);
        $result = $this->repository->findAll();
        $this->assertEmpty($result);
    }

    public function testFindAllReturnsAppointments(): void
    {
        $expected = [
            ['id' => 1, 'patient_name' => 'John Doe', 'doctor_name' => 'Dr. Smith'],
            ['id' => 2, 'patient_name' => 'Jane Smith', 'doctor_name' => 'Dr. Johnson']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->findAll();
        $this->assertCount(2, $result);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $this->mockStmt->method('fetch')->willReturn(false);
        $result = $this->repository->findById(999);
        $this->assertNull($result);
    }

    public function testFindByIdReturnsAppointment(): void
    {
        $expected = ['id' => 1, 'patient_name' => 'John Doe'];
        $this->mockStmt->method('fetch')->willReturn($expected);
        $result = $this->repository->findById(1);
        $this->assertEquals($expected, $result);
    }

    public function testFindByPatientIdReturnsAppointments(): void
    {
        $expected = [
            ['id' => 1, 'start_time' => '2024-06-15 10:00:00'],
            ['id' => 2, 'start_time' => '2024-06-16 14:00:00']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->findByPatientId(1);
        $this->assertCount(2, $result);
    }

    public function testFindByDoctorIdReturnsAppointments(): void
    {
        $expected = [
            ['id' => 1, 'patient_name' => 'John Doe'],
            ['id' => 2, 'patient_name' => 'Jane Smith']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->findByDoctorId(1);
        $this->assertCount(2, $result);
    }

    public function testFindUpcomingReturnsAppointments(): void
    {
        $expected = [
            ['id' => 1, 'patient_name' => 'John Doe'],
            ['id' => 2, 'patient_name' => 'Jane Smith']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->findUpcoming();
        $this->assertCount(2, $result);
    }

    public function testIsPatientAssignedToDoctorReturnsTrue(): void
    {
        $this->mockStmt->method('fetchColumn')->willReturn(1);
        $result = $this->repository->isPatientAssignedToDoctor(1, 1);
        $this->assertTrue($result);
    }

    public function testIsPatientAssignedToDoctorReturnsFalse(): void
    {
        $this->mockStmt->method('fetchColumn')->willReturn(0);
        $result = $this->repository->isPatientAssignedToDoctor(1, 1);
        $this->assertFalse($result);
    }

    public function testIsAppointmentOwnedByDoctorReturnsTrue(): void
    {
        $this->mockStmt->method('fetchColumn')->willReturn(1);
        $result = $this->repository->isAppointmentOwnedByDoctor(1, 1);
        $this->assertTrue($result);
    }

    public function testGenerateWaitlistTicketFormat(): void
    {
        $this->mockStmt->method('fetchColumn')->willReturn(5);
        $result = $this->repository->generateWaitlistTicket();
        $this->assertStringStartsWith('WL-', $result);
        $this->assertStringContainsString('-00006', $result);
    }

    public function testCountScheduledByDate(): void
    {
        $this->mockStmt->method('fetchColumn')->willReturn(10);
        $result = $this->repository->countScheduledByDate('2024-06-15');
        $this->assertEquals(10, $result);
    }

    public function testCountAppointmentsByDate(): void
    {
        $this->mockStmt->method('fetchColumn')->willReturn(15);
        $result = $this->repository->countAppointmentsByDate('2024-06-15');
        $this->assertEquals(15, $result);
    }

    public function testFindByDateRange(): void
    {
        $expected = [
            ['id' => 1, 'start_time' => '2024-06-15 10:00:00'],
            ['id' => 2, 'start_time' => '2024-06-15 14:00:00']
        ];
        $this->mockStmt->method('fetchAll')->willReturn($expected);
        $result = $this->repository->findByDateRange('2024-06-15 00:00:00', '2024-06-15 23:59:59');
        $this->assertCount(2, $result);
    }
}

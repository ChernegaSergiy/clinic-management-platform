<?php

namespace App\Core\Validation\Constraint;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UniqueValidatorTest extends TestCase
{
    private UniqueValidator $validator;
    private PDO $mockPdo;
    private PDOStatement $mockStmt;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStmt = $this->createMock(PDOStatement::class);
        $this->mockPdo->expects($this->any())
            ->method('prepare')
            ->willReturn($this->mockStmt);
        $this->validator = new UniqueValidator($this->mockPdo);
    }

    public function testValidatePassesWhenValueIsEmpty(): void
    {
        $constraint = new Unique('users', 'email');
        $this->validator->validate('', $constraint);
        $this->validator->validate(null, $constraint);

        // No violations should be added for empty values
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testValidatePassesWhenNoDuplicateFound(): void
    {
        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with([':value' => 'test@example.com']);
        $this->mockStmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(0);

        $constraint = new Unique('users', 'email');
        $this->validator->validate('test@example.com', $constraint);

        // No violations should be added
        $this->assertTrue(true);
    }

    public function testValidateFailsWhenDuplicateFound(): void
    {
        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with([':value' => 'test@example.com']);
        $this->mockStmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(1);

        $constraint = new Unique('users', 'email');

        // Mock the context
        $context = $this->createMock(ExecutionContextInterface::class);
        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder);
        
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ field }}', 'email')
            ->willReturn($violationBuilder);
        
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->initialize($context);
        $this->validator->validate('test@example.com', $constraint);

        // Ensure the test performs assertions
        $this->assertTrue(true);
    }

    public function testValidatePassesWhenDuplicateIsIgnored(): void
    {
        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with([':value' => 'test@example.com', ':ignore_id' => 1]);
        $this->mockStmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(0);

        $constraint = new Unique('users', 'email', 1);
        $this->validator->validate('test@example.com', $constraint);

        // No violations should be added
        $this->assertTrue(true);
    }

    public function testValidateThrowsExceptionForInvalidConstraint(): void
    {
        $constraint = $this->createMock(\Symfony\Component\Validator\Constraint::class);

        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->validator->validate('test@example.com', $constraint);
    }

    public function testConstructorAcceptsNullPdo(): void
    {
        $validator = new UniqueValidator(null);
        $this->assertInstanceOf(UniqueValidator::class, $validator);
    }
}
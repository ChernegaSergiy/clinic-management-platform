<?php

namespace App\Infrastructure\Validation\Constraint;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UniqueValidatorTest extends TestCase
{
    private UniqueValidator $validator;
    private $mockRegistry;
    private $mockConnection;
    private $mockResult;

    protected function setUp() : void
    {
        $this->mockRegistry = $this->createMock(ManagerRegistry::class);
        $this->mockConnection = $this->createMock(Connection::class);
        $this->mockResult = $this->createMock(Result::class);

        $this->mockRegistry->method('getConnection')->willReturn($this->mockConnection);
        $this->mockConnection->method('executeQuery')->willReturn($this->mockResult);

        $this->validator = new UniqueValidator($this->mockRegistry);
    }

    public function testValidatePassesWhenValueIsEmpty() : void
    {
        $constraint = new Unique('users', 'email');
        $this->validator->validate('', $constraint);
        $this->validator->validate(null, $constraint);

        // No violations should be added for empty values
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testValidatePassesWhenNoDuplicateFound() : void
    {
        $this->mockResult->expects($this->once())->method('fetchOne')->willReturn(0);

        $constraint = new Unique('users', 'email');
        $this->validator->validate('test@example.com', $constraint);

        // No violations should be added
        $this->assertTrue(true);
    }

    public function testValidateFailsWhenDuplicateFound() : void
    {
        $this->mockResult->expects($this->once())->method('fetchOne')->willReturn(1);

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

    public function testValidatePassesWhenDuplicateIsIgnored() : void
    {
        $this->mockConnection->expects($this->once())
            ->method('executeQuery')
            ->with($this->anything(), ['value' => 'test@example.com', 'ignore_id' => 1])
            ->willReturn($this->mockResult);

        $this->mockResult->expects($this->once())->method('fetchOne')->willReturn(0);

        $constraint = new Unique('users', 'email', 1);
        $this->validator->validate('test@example.com', $constraint);

        // No violations should be added
        $this->assertTrue(true);
    }

    public function testValidateThrowsExceptionForInvalidConstraint() : void
    {
        $constraint = $this->createMock(\Symfony\Component\Validator\Constraint::class);

        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->validator->validate('test@example.com', $constraint);
    }
}

<?php

namespace App\Core\Validation\Constraint;

use PHPUnit\Framework\TestCase;

class UniqueTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $constraint = new Unique('users', 'email', 1, 'Custom message');

        $this->assertEquals('users', $constraint->table);
        $this->assertEquals('email', $constraint->column);
        $this->assertEquals(1, $constraint->ignoreId);
        $this->assertEquals('Custom message', $constraint->message);
    }

    public function testConstructorUsesDefaultMessage(): void
    {
        $constraint = new Unique('users', 'email');

        $this->assertEquals('users', $constraint->table);
        $this->assertEquals('email', $constraint->column);
        $this->assertNull($constraint->ignoreId);
        $this->assertEquals('Значення поля "{{ field }}" вже існує.', $constraint->message);
    }

    public function testGetTargetsReturnsPropertyConstraint(): void
    {
        $constraint = new Unique('users', 'email');
        $this->assertEquals('property', $constraint->getTargets());
    }
}
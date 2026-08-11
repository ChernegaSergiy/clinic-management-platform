<?php

namespace App\Shared\Validation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $validator;
    private $mockRegistry;
    private $mockConnection;
    private $mockResult;

    protected function setUp() : void
    {
        $this->mockRegistry = $this->createMock(ManagerRegistry::class);
        $this->mockConnection = $this->createMock(Connection::class);
        $this->mockResult = $this->createMock(Result::class);

        // We only map this for when the validator actually tries to query DB (for 'unique' rules)
        $this->mockRegistry->method('getConnection')->willReturn($this->mockConnection);
        $this->mockConnection->method('executeQuery')->willReturn($this->mockResult);

        $this->validator = new Validator($this->mockRegistry);
    }

    public function testValidateWithNoRulesReturnsTrue() : void
    {
        $result = $this->validator->validate([], []);
        $this->assertTrue($result);
    }

    public function testRequiredRuleFailsWhenFieldEmpty() : void
    {
        $result = $this->validator->validate(['name' => ''], ['name' => ['required']]);
        $this->assertFalse($result);
        $this->assertNotEmpty($this->validator->getErrors()['name']);
    }

    public function testRequiredRuleFailsWhenFieldMissing() : void
    {
        $result = $this->validator->validate([], ['name' => ['required']]);
        $this->assertFalse($result);
        $this->assertNotEmpty($this->validator->getErrors()['name']);
    }

    public function testRequiredRulePassesWhenFieldHasValue() : void
    {
        $result = $this->validator->validate(['name' => 'John'], ['name' => ['required']]);
        $this->assertTrue($result);
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testEmailRuleFailsOnInvalidEmail() : void
    {
        $result = $this->validator->validate(['email' => 'invalid'], ['email' => ['email']]);
        $this->assertFalse($result);
        $this->assertNotEmpty($this->validator->getErrors()['email']);
    }

    public function testEmailRulePassesOnValidEmail() : void
    {
        $result = $this->validator->validate(['email' => 'test@example.com'], ['email' => ['email']]);
        $this->assertTrue($result);
    }

    public function testEmailRulePassesOnEmptyEmail() : void
    {
        $result = $this->validator->validate(['email' => ''], ['email' => ['email']]);
        $this->assertTrue($result);
    }

    public function testMinRuleFailsWhenTooShort() : void
    {
        $result = $this->validator->validate(['password' => '123'], ['password' => ['min:8']]);
        $this->assertFalse($result);
        $this->assertNotEmpty($this->validator->getErrors()['password']);
    }

    public function testMinRulePassesWhenLongEnough() : void
    {
        $result = $this->validator->validate(['password' => '12345678'], ['password' => ['min:8']]);
        $this->assertTrue($result);
    }

    public function testDateRuleFailsOnInvalidDate() : void
    {
        $result = $this->validator->validate(['date' => 'not-a-date'], ['date' => ['date']]);
        $this->assertFalse($result);
        $this->assertNotEmpty($this->validator->getErrors()['date']);
    }

    public function testDateRulePassesOnValidDate() : void
    {
        $result = $this->validator->validate(['date' => '2024-06-15'], ['date' => ['date']]);
        $this->assertTrue($result);
    }

    public function testDatetimeRulePassesWithSeconds() : void
    {
        $result = $this->validator->validate(['datetime' => '2024-06-15 10:30:45'], ['datetime' => ['datetime']]);
        $this->assertTrue($result);
    }

    public function testDatetimeRulePassesWithoutSeconds() : void
    {
        $result = $this->validator->validate(['datetime' => '2024-06-15 10:30'], ['datetime' => ['datetime']]);
        $this->assertTrue($result);
    }

    public function testDatetimeRuleFailsOnInvalidFormat() : void
    {
        $result = $this->validator->validate(['datetime' => '15-06-2024 10:30'], ['datetime' => ['datetime']]);
        $this->assertFalse($result);
    }

    public function testInRuleFailsWhenValueNotInList() : void
    {
        $result = $this->validator->validate(['status' => 'unknown'], ['status' => ['in:active,inactive,pending']]);
        $this->assertFalse($result);
        $this->assertNotEmpty($this->validator->getErrors()['status']);
    }

    public function testInRulePassesWhenValueInList() : void
    {
        $result = $this->validator->validate(['status' => 'active'], ['status' => ['in:active,inactive,pending']]);
        $this->assertTrue($result);
    }

    public function testNumericRuleFailsOnNonNumeric() : void
    {
        $result = $this->validator->validate(['age' => 'abc'], ['age' => ['numeric']]);
        $this->assertFalse($result);
    }

    public function testNumericRulePassesOnNumeric() : void
    {
        $result = $this->validator->validate(['age' => '25'], ['age' => ['numeric']]);
        $this->assertTrue($result);
    }

    public function testNumericRulePassesOnFloatString() : void
    {
        $result = $this->validator->validate(['price' => '19.99'], ['price' => ['numeric']]);
        $this->assertTrue($result);
    }

    public function testMinValueRuleFailsWhenBelowMin() : void
    {
        $result = $this->validator->validate(['quantity' => 5], ['quantity' => ['min_value:10']]);
        $this->assertFalse($result);
    }

    public function testMinValueRulePassesWhenAboveMin() : void
    {
        $result = $this->validator->validate(['quantity' => 15], ['quantity' => ['min_value:10']]);
        $this->assertTrue($result);
    }

    public function testMaxValueRuleFailsWhenAboveMax() : void
    {
        $result = $this->validator->validate(['age' => 150], ['age' => ['max_value:120']]);
        $this->assertFalse($result);
    }

    public function testMaxValueRulePassesWhenBelowMax() : void
    {
        $result = $this->validator->validate(['age' => 30], ['age' => ['max_value:120']]);
        $this->assertTrue($result);
    }

    public function testArrayRuleFailsWhenNotArray() : void
    {
        $result = $this->validator->validate(['items' => 'not-array'], ['items' => ['array']]);
        $this->assertFalse($result);
    }

    public function testArrayRulePassesWhenArray() : void
    {
        $result = $this->validator->validate(['items' => [1, 2, 3]], ['items' => ['array']]);
        $this->assertTrue($result);
    }

    public function testUniqueRulePassesWhenNoDuplicate() : void
    {
        $this->mockResult->expects($this->once())
            ->method('fetchOne')
            ->willReturn(0);
        $result = $this->validator->validate(['email' => 'new@example.com'], ['email' => ['unique:users,email']]);
        $this->assertTrue($result);
    }

    public function testUniqueRuleFailsWhenDuplicateExists() : void
    {
        $this->mockResult->expects($this->once())
            ->method('fetchOne')
            ->willReturn(1);
        $result = $this->validator->validate(['email' => 'existing@example.com'], ['email' => ['unique:users,email']]);
        $this->assertFalse($result);
        $this->assertNotEmpty($this->validator->getErrors()['email']);
    }

    public function testUniqueRulePassesWhenDuplicateIsIgnoredId() : void
    {
        $this->mockResult->expects($this->once())
            ->method('fetchOne')
            ->willReturn(0); // No duplicates found (current record ignored)
        $result = $this->validator->validate(['email' => 'existing@example.com'], ['email' => ['unique:users,email,1']]);
        $this->assertTrue($result);
    }

    public function testUniqueRuleValidatesZeroValue() : void
    {
        $this->mockConnection->expects($this->once())
            ->method('executeQuery')
            ->with($this->anything(), ['value' => '0'])
            ->willReturn($this->mockResult);

        $this->mockResult->expects($this->once())
            ->method('fetchOne')
            ->willReturn(0); // No duplicates found
        $result = $this->validator->validate(['status' => '0'], ['status' => ['unique:users,status']]);
        $this->assertTrue($result);
    }

    public function testHasErrorsReturnsTrueWhenErrorsExist() : void
    {
        $this->validator->validate(['name' => ''], ['name' => ['required']]);
        $this->assertTrue($this->validator->hasErrors());
    }

    public function testHasErrorsReturnsFalseWhenNoErrors() : void
    {
        $this->validator->validate(['name' => 'John'], ['name' => ['required']]);
        $this->assertFalse($this->validator->hasErrors());
    }

    public function testGetErrorsReturnsAllErrors() : void
    {
        $this->validator->validate([
            'email' => 'invalid',
            'name' => ''
        ], [
            'email' => ['email'],
            'name' => ['required']
        ]);
        $errors = $this->validator->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testAddErrorManually() : void
    {
        $this->validator->addError('custom', 'Custom error message');
        $this->assertTrue($this->validator->hasErrors());
        $this->assertContains('Custom error message', $this->validator->getErrors()['custom']);
    }

    public function testMultipleRulesOnSameField() : void
    {
        $result = $this->validator->validate([
            'password' => '123'
        ], [
            'password' => ['required', 'min:8']
        ]);
        $this->assertFalse($result);
        $errors = $this->validator->getErrors()['password'];
        $this->assertCount(1, $errors);
    }
}

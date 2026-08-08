<?php

namespace App\Tests\Core\Auth;

use App\Core\Auth\PolicyRegistry;
use PHPUnit\Framework\TestCase;

class PolicyRegistryTest extends TestCase
{
    public function testRegisterAndGetReturnsNewPolicyInstance() : void
    {
        $registry = new PolicyRegistry();

        $registry->register('patient', DummyPolicy::class);
        $policy = $registry->get('patient');

        $this->assertInstanceOf(DummyPolicy::class, $policy);
    }

    public function testGetReturnsNullForUnregisteredResourceKey() : void
    {
        $registry = new PolicyRegistry();

        $this->assertNull($registry->get('unregistered'));
    }

    public function testRegisterIgnoresNonExistentPolicyClass() : void
    {
        $registry = new PolicyRegistry();

        $registry->register('patient', 'App\\Nonexistent\\PolicyClass');

        $this->assertNull($registry->get('patient'));
    }

    public function testGetReturnsFreshInstanceOnEachCall() : void
    {
        $registry = new PolicyRegistry();
        $registry->register('patient', DummyPolicy::class);

        $first = $registry->get('patient');
        $second = $registry->get('patient');

        $this->assertNotSame($first, $second);
    }
}

class DummyPolicy {}

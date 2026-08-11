<?php

namespace App\Tests\Shared\Exception;

use App\Shared\Exception\RedirectException;
use PHPUnit\Framework\TestCase;

class RedirectExceptionTest extends TestCase
{
    public function testExposesTargetUrl() : void
    {
        $exception = new RedirectException('/login');

        $this->assertSame('/login', $exception->getUrl());
        $this->assertSame('Redirect to: /login', $exception->getMessage());
    }

    public function testKeepsPreviousException() : void
    {
        $previous = new \RuntimeException('auth failed');

        $exception = new RedirectException('/login', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}

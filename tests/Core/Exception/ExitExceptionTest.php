<?php

namespace App\Tests\Core\Exception;

use App\Core\Exception\ExitException;
use PHPUnit\Framework\TestCase;

class ExitExceptionTest extends TestCase
{
    public function testDefaultsToEmptyMessageAndStatusCode200() : void
    {
        $exception = new ExitException();

        $this->assertSame('', $exception->getExitMessage());
        $this->assertSame(200, $exception->getStatusCode());
    }

    public function testExposesGivenMessageAndStatusCode() : void
    {
        $exception = new ExitException('Access denied', 403);

        $this->assertSame('Access denied', $exception->getExitMessage());
        $this->assertSame(403, $exception->getStatusCode());
        $this->assertSame('Access denied', $exception->getMessage());
        $this->assertSame(403, $exception->getCode());
    }

    public function testKeepsPreviousException() : void
    {
        $previous = new \RuntimeException('root cause');

        $exception = new ExitException('Failure', 500, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}

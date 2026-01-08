<?php

namespace App\Core\Exception;

use RuntimeException;

class ExitException extends RuntimeException
{
    private int $statusCode;

    public function __construct(string $message = '', int $statusCode = 200, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
    }

    public function getExitMessage(): string
    {
        return $this->getMessage();
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

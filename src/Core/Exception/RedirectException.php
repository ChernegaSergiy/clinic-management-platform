<?php

namespace App\Core\Exception;

use RuntimeException;

class RedirectException extends RuntimeException
{
    private string $url;

    public function __construct(string $url, ?\Throwable $previous = null)
    {
        parent::__construct("Redirect to: {$url}", 0, $previous);
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}

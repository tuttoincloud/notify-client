<?php

namespace TuttoInCloud\NotifyClient\Exceptions;

use RuntimeException;

class NotifyException extends RuntimeException
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

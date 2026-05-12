<?php

namespace TuttoInCloud\NotifyClient\Exceptions;

class NotifyValidationException extends NotifyException
{
    protected array $errors;

    public function __construct(string $message, array $errors = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}

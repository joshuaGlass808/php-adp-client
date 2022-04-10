<?php

namespace Jlg\ADP\Exceptions;

class ADPClientValidationException extends \Exception
{
    // Redefine the exception so message isn't optional
    public function __construct(string $message, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    // custom string representation of object
    public function __toString(): string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

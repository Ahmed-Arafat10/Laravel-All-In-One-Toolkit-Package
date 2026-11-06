<?php

namespace AhmedArafat\AllInOne\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * Identify exceptions rendered as an array of validation messages by ExceptionHelper.
 */
class ValidationErrorsAsArrayException extends Exception
{
    /**
     * Pass the validation message, code and previous exception to the base exception.
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

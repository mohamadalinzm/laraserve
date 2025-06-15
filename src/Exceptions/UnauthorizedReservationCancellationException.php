<?php

namespace Nazemi\Laraserve\Exceptions;

use Exception;
use Throwable;

class UnauthorizedReservationCancellationException extends Exception
{
    /**
     * Create a new UnauthorizedReservationCancellationException instance.
     */
    public function __construct(
        string $message = 'You are not authorized to cancel this reservation.',
        int $code = 403,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

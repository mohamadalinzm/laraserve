<?php

namespace Nazemi\Laraserve\Exceptions;

use Exception;
use Throwable;

class ExpiredReservationException extends Exception
{
    /**
     * Create a new ExpiredReservationException instance.
     */
    public function __construct(
        string $message = 'Reservations in the past cannot be booked or cancelled.',
        int $code = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

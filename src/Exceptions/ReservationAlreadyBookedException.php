<?php

namespace Nazemi\Laraserve\Exceptions;

use Exception;
use Throwable;

class ReservationAlreadyBookedException extends Exception
{
    /**
     * Create a new ReservationAlreadyBookedException instance.
     */
    public function __construct(
        string $message = 'Reservation is already booked by another recipient.',
        int $code = 400,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

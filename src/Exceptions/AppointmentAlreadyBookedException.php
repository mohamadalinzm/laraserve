<?php

namespace Nzm\Appointment\Exceptions;

use Exception;
use Throwable;

class AppointmentAlreadyBookedException extends Exception
{
    /**
     * Create a new AppointmentAlreadyBookedException instance.
     */
    public function __construct(
        string $message = 'Appointment is already booked by another client.',
        int $code = 400,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

<?php

namespace Nzm\Appointment\Exceptions;

use Exception;
use Throwable;

class ExpiredAppointmentException extends Exception
{
    /**
     * Create a new ExpiredAppointmentException instance.
     */
    public function __construct(
        string $message = 'Appointments in the past cannot be booked or cancelled.',
        int $code = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

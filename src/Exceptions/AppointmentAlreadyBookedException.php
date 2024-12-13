<?php

namespace Nzm\Appointment\Exceptions;

use Exception;
use Throwable;

class AppointmentAlreadyBookedException extends Exception
{
    /**
     * Create a new AppointmentAlreadyBookedException instance.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = 'Appointment is already booked by another client.',
        int $code = 400,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
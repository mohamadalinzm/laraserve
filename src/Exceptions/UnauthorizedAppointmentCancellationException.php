<?php

namespace Nzm\Appointment\Exceptions;

use Exception;
use Throwable;

class UnauthorizedAppointmentCancellationException extends Exception
{
    /**
     * Create a new UnauthorizedAppointmentCancellationException instance.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = 'You are not authorized to cancel this appointment.',
        int $code = 403,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
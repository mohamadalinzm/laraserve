<?php

namespace Nzm\Appointment\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nzm\Appointment\Exceptions\AppointmentAlreadyBookedException;
use Nzm\Appointment\Exceptions\ExpiredAppointmentException;
use Nzm\Appointment\Exceptions\UnauthorizedAppointmentCancellationException;
use Nzm\Appointment\Models\Appointment;

trait Clientable
{
    public function clientAppointments(): MorphMany
    {
        return $this->morphMany(Appointment::class, 'clientable');
    }

    public function getBookedSlots(): Collection
    {
        return $this->clientAppointments()
            ->where('start_time', '>', now())
            ->get();
    }

    public function getUpComingBookedSlots(): Collection
    {
        return $this->clientAppointments()
            ->where('start_time', '>', now())
            ->get();
    }

    public function bookAppointment(Appointment $appointment): Appointment
    {
        if ($appointment->start_time < now()) {
            throw new ExpiredAppointmentException;
        }

        if ($appointment->clientable_id !== null) {
            throw new AppointmentAlreadyBookedException;
        }

        return tap($appointment)->update([
            'clientable_id' => $this->id,
            'clientable_type' => get_class($this),
        ]);
    }

    public function cancelAppointment(Appointment $appointment)
    {
        if ($appointment->start_time < now()) {
            throw new ExpiredAppointmentException;
        }

        if ($appointment->clientable_id !== $this->id) {
            throw new UnauthorizedAppointmentCancellationException;
        }

        return tap($appointment)->update([
            'clientable_id' => null,
            'clientable_type' => null,
        ]);
    }
}

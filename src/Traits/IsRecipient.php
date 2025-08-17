<?php

namespace Nazemi\Laraserve\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nazemi\Laraserve\Exceptions\ReservationAlreadyBookedException;
use Nazemi\Laraserve\Exceptions\ExpiredReservationException;
use Nazemi\Laraserve\Exceptions\UnauthorizedReservationCancellationException;
use Nazemi\Laraserve\Models\Reservation;

trait IsRecipient
{
    public function receivedReservations(): MorphMany
    {
        return $this->morphMany(Reservation::class, 'recipient');
    }

    public function getUpcomingReservations(): Collection
    {
        return $this->receivedReservations()
            ->where('start_time', '>', now())
            ->get();
    }

    public function reserve(Reservation $reservation): Reservation
    {
        if ($reservation->start_time < now()) {
            throw new ExpiredReservationException;
        }

        if ($reservation->recipient_id !== null) {
            throw new ReservationAlreadyBookedException;
        }

        return tap($reservation)->update([
            'recipient_id' => $this->id,
            'recipient_type' => get_class($this),
        ]);
    }

    public function cancel(Reservation $reservation)
    {
        if ($reservation->start_time < now()) {
            throw new ExpiredReservationException;
        }

        if ($reservation->recipient_id !== $this->id) {
            throw new UnauthorizedReservationCancellationException;
        }

        return tap($reservation)->update([
            'recipient_id' => null,
            'recipient_type' => null,
        ]);
    }
}

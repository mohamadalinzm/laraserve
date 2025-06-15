<?php

namespace Nazemi\Laraserve\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nazemi\Laraserve\Exceptions\ReservationAlreadyBookedException;
use Nazemi\Laraserve\Exceptions\ExpiredReservationException;
use Nazemi\Laraserve\Exceptions\UnauthorizedReservationCancellationException;
use Nazemi\Laraserve\Models\Reservation;

trait Clientable
{
    public function clientReservations(): MorphMany
    {
        return $this->morphMany(Reservation::class, 'clientable');
    }

    public function getClientBookedSlots(): Collection
    {
        return $this->clientReservations()
            ->where('start_time', '>', now())
            ->get();
    }

    public function getClientUpcomingBookedSlots(): Collection
    {
        return $this->clientReservations()
            ->where('start_time', '>', now())
            ->get();
    }

    public function bookReservation(Reservation $reservation): Reservation
    {
        if ($reservation->start_time < now()) {
            throw new ExpiredReservationException;
        }

        if ($reservation->clientable_id !== null) {
            throw new ReservationAlreadyBookedException;
        }

        return tap($reservation)->update([
            'clientable_id' => $this->id,
            'clientable_type' => get_class($this),
        ]);
    }

    public function cancelReservation(Reservation $reservation)
    {
        if ($reservation->start_time < now()) {
            throw new ExpiredReservationException;
        }

        if ($reservation->clientable_id !== $this->id) {
            throw new UnauthorizedReservationCancellationException;
        }

        return tap($reservation)->update([
            'clientable_id' => null,
            'clientable_type' => null,
        ]);
    }
}

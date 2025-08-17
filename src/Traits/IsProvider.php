<?php

namespace Nazemi\Laraserve\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nazemi\Laraserve\Models\Reservation;

trait IsProvider
{
    public function providedReservations(): MorphMany
    {
        return $this->morphMany(Reservation::class, 'provider');
    }

    public function getAvailableSlots(): Collection
    {
        return $this->providedReservations()
            ->whereNull('recipient_id')
            ->where('start_time', '>', now())
            ->get();
    }

    public function getBookedSlots(): Collection
    {
        return $this->providedReservations()
            ->whereNotNull('recipient_id')
            ->get();
    }

    public function getUpcomingBookedSlots(): Collection
    {
        return $this->providedReservations()
            ->whereNotNull('recipient_id')
            ->where('start_time', '>', now())
            ->get();
    }

    public function getSlotsByDate($date): Collection
    {
        return $this->providedReservations()
            ->whereDate('start_time', $date)
            ->get();
    }

    public function findSlotByDate($date): Reservation
    {
        return $this->providedReservations()
            ->where('start_time', $date)
            ->firstOrFail();
    }
}
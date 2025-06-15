<?php

namespace Nazemi\Laraserve\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nazemi\Laraserve\Models\Reservation;

trait Agentable
{
    public function agentReservations(): MorphMany
    {
        return $this->morphMany(Reservation::class, 'agentable');
    }

    public function getAvailableSlots(): Collection
    {
        return $this->agentReservations()
            ->whereNull('clientable_id')
            ->where('start_time', '>', now())
            ->get();
    }

    public function getAgentBookedSlots(): Collection
    {
        return $this->agentReservations()
            ->whereNotNull('clientable_id')
            ->get();
    }

    public function getAgentUpcomingBookedSlots(): Collection
    {
        return $this->agentReservations()
            ->whereNotNull('clientable_id')
            ->where('start_time', '>', now())
            ->get();
    }

    public function getSlotsByDate($date): Collection
    {
        return $this->agentReservations()
            ->whereDate('start_time', $date)
            ->get();
    }

    public function findSlotByDate($date): Reservation
    {
        return $this->agentReservations()
            ->where('start_time', $date)
            ->firstOrFail();
    }
}
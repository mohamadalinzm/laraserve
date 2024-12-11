<?php

namespace Nzm\Appointment\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nzm\Appointment\Models\Appointment;

trait Agentable
{
    public function agentAppointments(): MorphMany
    {
        return $this->morphMany(Appointment::class, 'agentable');
    }

    public function getAvailableSlots(): Collection
    {
        return $this->agentAppointments()
            ->whereNull('clientable_id')
            ->where('start_time', '>', now())
            ->get();
    }

    public function getBookedSlots(): Collection
    {
        return $this->agentAppointments()
            ->whereNotNull('clientable_id')
            ->get();
    }

    public function getUpComingBookedSlots(): Collection
    {
        return $this->agentAppointments()
            ->whereNotNull('clientable_id')
            ->where('start_time', '>', now())
            ->get();
    }

    public function getSlotsByDate($date): Collection
    {
        return $this->agentAppointments()
            ->whereDate('start_time', $date)
            ->get();
    }
}

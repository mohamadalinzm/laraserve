<?php

namespace Nzm\Appointment\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nzm\Appointment\Models\Appointment;

trait Agentable
{
    public function appointments(): MorphMany
    {
        return $this->morphMany(Appointment::class, 'agentable');
    }

    public function getAvailableSlots(): Collection
    {
        return $this->appointments()
            ->whereNull('clientable_id')
            ->where('start_time', '>', now())
            ->get();
    }

    public function getBookedSlots(): Collection
    {
        return $this->appointments()
            ->whereNotNull('clientable_id')
            ->get();
    }

    public function getUpComingBookedSlots(): Collection
    {
        return $this->appointments()
            ->whereNotNull('clientable_id')
            ->where('start_time', '>', now())
            ->get();
    }
}

<?php

namespace Nzm\LaravelAppointment\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nzm\LaravelAppointment\Models\Appointment;

trait Clientable
{

    public function clientAppointments(): MorphMany
    {
        return $this->morphMany(Appointment::class, 'clientable');
    }

}

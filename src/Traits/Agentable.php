<?php

namespace Nzm\LaravelAppointment\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nzm\LaravelAppointment\Models\Appointment;

trait Agentable
{

    public function agentAppointments(): MorphMany
    {
        return $this->morphMany(Appointment::class, 'agentable');
    }

}

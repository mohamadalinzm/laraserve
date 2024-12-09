<?php

namespace Nzm\Appointment\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nzm\Appointment\Models\Appointment;

trait Agentable
{

    public function agentAppointments(): MorphMany
    {
        return $this->morphMany(Appointment::class, 'agentable');
    }

}

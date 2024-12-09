<?php

namespace Nzm\Appointment\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nzm\Appointment\Models\Appointment;

trait Clientable
{

    public function clientAppointments(): MorphMany
    {
        return $this->morphMany(Appointment::class, 'clientable');
    }

}

<?php

namespace Nzm\LaravelAppointment\Facades;

use Appointment\Builder\AppointmentBuilder;
use Illuminate\Support\Facades\Facade;


class AppointmentFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppointmentBuilder::class;
    }

}

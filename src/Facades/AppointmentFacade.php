<?php

namespace Nzm\LaravelAppointment\Facades;

use Illuminate\Support\Facades\Facade;
use Nzm\LaravelAppointment\Builder\AppointmentBuilder;


class AppointmentFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppointmentBuilder::class;
    }

}

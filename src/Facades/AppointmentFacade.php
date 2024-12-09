<?php

namespace Nzm\Appointment\Facades;

use Illuminate\Support\Facades\Facade;
use Nzm\Appointment\Builder\AppointmentBuilder;


class AppointmentFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppointmentBuilder::class;
    }

}

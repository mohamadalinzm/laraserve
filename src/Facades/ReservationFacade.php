<?php

namespace Nazemi\Laraserve\Facades;

use Illuminate\Support\Facades\Facade;
use Nazemi\Laraserve\Builder\ReservationBuilder;

class ReservationFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ReservationBuilder::class;
    }
}

<?php

namespace Nzm\LaravelAppointment;


use Illuminate\Support\ServiceProvider;

class AppointmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

}

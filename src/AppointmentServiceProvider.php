<?php

namespace Nzm\LaravelAppointment;


use Illuminate\Support\ServiceProvider;
use Nzm\LaravelAppointment\Builder\AppointmentBuilder;

class AppointmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AppointmentBuilder::class, function () {
            return new AppointmentBuilder();
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

}

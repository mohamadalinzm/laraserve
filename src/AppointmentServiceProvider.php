<?php

namespace Nzm\Appointment;

use Illuminate\Support\ServiceProvider;
use Nzm\Appointment\Builder\AppointmentBuilder;

class AppointmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AppointmentBuilder::class, function () {
            return new AppointmentBuilder;
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->publishes([
            __DIR__.'/config/appointment.php' => config_path('appointment.php'),
        ]);
    }
}

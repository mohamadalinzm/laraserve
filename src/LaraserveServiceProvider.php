<?php

namespace Nazemi\Laraserve;

use Illuminate\Support\ServiceProvider;
use Nazemi\Laraserve\Builder\ReservationBuilder;

class LaraserveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReservationBuilder::class, function () {
            return new ReservationBuilder;
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->publishes([
            __DIR__.'/config/laraserve.php' => config_path('laraserve.php'),
        ]);
    }
}

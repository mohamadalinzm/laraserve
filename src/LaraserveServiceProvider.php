<?php

namespace Nazemi\Laraserve;

use Illuminate\Support\ServiceProvider;
use Nazemi\Laraserve\Builder\ReservationBuilder;

class LaraserveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/laraserve.php', 'laraserve');

        $this->app->singleton(ReservationBuilder::class);
        $this->app->bind('Laraserve', ReservationBuilder::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        if ($this->app->runningInConsole())
        {
            $this->publishes([
                __DIR__.'/config/laraserve.php' => config_path('laraserve.php'),
            ]);

            $this->publishes([
                __DIR__.'/database/migrations' => database_path('migrations'),
            ], 'laraserve-migrations');
        }

    }
}

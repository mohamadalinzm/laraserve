<?php

namespace Nazemi\Laraserve\Tests\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Nazemi\Laraserve\Tests\TestModels\Provider;
use Nazemi\Laraserve\Tests\TestModels\Recipient;
use Nazemi\Laraserve\Tests\TestModels\User;

trait SetUpDatabase
{
    protected $provider;
    protected $providerUser;

    protected $recipient;
    protected $recipientUser;

    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();

        $this->faker = \Faker\Factory::create();
        $this->provider = Provider::query()->create(['name' => $this->faker->name]);
        $this->providerUser = User::query()->create(['name' => $this->faker->name,'role' => 'provider']);
        $this->recipient = Recipient::query()->create(['name' => $this->faker->name]);
        $this->recipientUser = User::query()->create(['name' => $this->faker->name,'role' => 'recipient']);
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Configure database connection
        $app['config']->set('database.default', 'testdb');
        $app['config']->set('database.connections.testdb', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../src/database/migrations');

        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('recipients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('role', ['provider', 'recipient']);
            $table->timestamps();
        });
    }

    protected function generateReservation(): array
    {
        // Generate a random start time within the next month
        $startTime = $this->faker->dateTimeBetween('now', '+1 month');

        // Assume duration is in minutes; adjust as needed
        $durationMinutes = $this->faker->numberBetween(30, 120);

        // Calculate end time based on start time and duration
        $endTime = (clone $startTime)->modify("+{$durationMinutes} minutes");

        return [
            'provider_type' => Provider::class,
            'provider_id' => $this->provider->id,

            'recipient_type' => Recipient::class,
            'recipient_id' => $this->recipient->id,

            'start_time' => $startTime->format('Y-m-d H:i'),
            'end_time' => $endTime->format('Y-m-d H:i'),
        ];
    }
}

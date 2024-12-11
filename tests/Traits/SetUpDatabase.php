<?php

namespace Nzm\Appointment\Tests\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Nzm\Appointment\Tests\TestModels\Agent;
use Nzm\Appointment\Tests\TestModels\Client;

trait SetUpDatabase
{
    protected $agent;

    protected $client;

    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();

        $this->faker = \Faker\Factory::create();
        $this->agent = Agent::query()->create(['name' => $this->faker->name]);
        $this->client = Client::query()->create(['name' => $this->faker->name]);
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

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function generateAppointment(): array
    {
        // Generate a random start time within the next month
        $startTime = $this->faker->dateTimeBetween('now', '+1 month');

        // Assume duration is in minutes; adjust as needed
        $durationMinutes = $this->faker->numberBetween(30, 120);

        // Calculate end time based on start time and duration
        $endTime = (clone $startTime)->modify("+{$durationMinutes} minutes");

        return [
            'agentable_type' => Agent::class,
            'agentable_id' => $this->agent->id,

            'clientable_type' => Client::class,
            'clientable_id' => $this->client->id,

            'start_time' => $startTime->format('Y-m-d H:i'),
            'end_time' => $endTime->format('Y-m-d H:i'),
        ];
    }
}

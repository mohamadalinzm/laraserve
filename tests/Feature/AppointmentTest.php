<?php

namespace Nzm\LaravelAppointment\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Nzm\LaravelAppointment\Facades\AppointmentFacade;
use Orchestra\Testbench\TestCase;
use Nzm\LaravelAppointment\Models\Appointment;
use Nzm\LaravelAppointment\Tests\TestModels\Agent;
use Nzm\LaravelAppointment\Tests\TestModels\Client;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

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
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }


    protected function setUpDatabase(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../src/database/migrations');

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

    public function testCreateAppointment()
    {
        //Arrange
        $data = $this->generateAppointment();
        //Act
        $appointment = Appointment::query()->create($data);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', $data);
    }

    public function testBaseAddAppointmentWithBuilder()
    {
        $appointment = AppointmentFacade::setAgent($this->agent)
            ->setClient($this->client)
            ->startTime(now()->format('Y-m-d H:i'))
            ->endTime(now()->addMinutes(30)->format('Y-m-d H:i'))
            ->save();

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', [
            'agentable_type' => get_class($this->agent),
            'agentable_id' => $this->agent->id,
            'clientable_type' => get_class($this->client),
            'clientable_id' => $this->client->id,
            'start_time' => $appointment->start_time,
            'end_time' => $appointment->end_time
        ]);
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
            'end_time' => $endTime->format('Y-m-d H:i')
        ];
    }

}

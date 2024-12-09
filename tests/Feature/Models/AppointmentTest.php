<?php

namespace Nzm\Appointment\Tests\Feature\Models;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Nzm\Appointment\Facades\AppointmentFacade;
use Nzm\Appointment\Models\Appointment;
use Nzm\Appointment\Tests\TestModels\Agent;
use Nzm\Appointment\Tests\TestModels\Client;
use Orchestra\Testbench\TestCase;

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
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../src/database/migrations');

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

    public function test_create_appointment()
    {
        //Arrange
        $data = $this->generateAppointment();
        //Act
        $appointment = Appointment::query()->create($data);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', $data);
    }

    public function test_create_appointment_with_builder()
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
            'end_time' => $appointment->end_time,
        ]);
    }

    public function test_add_appointment_via_count_and_duration_with_builder()
    {
        $duration = 30;
        $count = 3;
        $appointments = AppointmentFacade::setAgent($this->agent)
            ->setClient($this->client)
            ->startTime(now()->format('Y-m-d H:i'))
            ->duration($duration)
            ->count($count)
            ->save();

        $this->assertIsArray($appointments);
        $this->assertCount($count, $appointments);

        foreach ($appointments as $appointment) {
            $this->assertInstanceOf(Appointment::class, $appointment);
            $this->assertDatabaseHas('appointments', [
                'agentable_type' => get_class($this->agent),
                'agentable_id' => $this->agent->id,
                'clientable_type' => get_class($this->client),
                'clientable_id' => $this->client->id,
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time,
            ]);
        }
    }

    public function test_validation_on_add_appointment_via_count_and_without_duration_with_builder()
    {
        $this->expectException(ValidationException::class);

        try {

            AppointmentFacade::setAgent($this->agent)
                ->setClient($this->client)
                ->startTime(now()->format('Y-m-d H:i'))
                ->count(3)
                ->save();

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            $this->assertTrue($errors->has('end_time'), 'Validation error for end_time is missing');
            $this->assertTrue($errors->has('duration'), 'Validation error for duration is missing');

            throw $e;
        }
    }

    public function test_validation_on_add_appointment_via_duration_and_without_count_with_builder()
    {
        $this->expectException(ValidationException::class);

        try {

            AppointmentFacade::setAgent($this->agent)
                ->setClient($this->client)
                ->startTime(now()->format('Y-m-d H:i'))
                ->duration(30)
                ->save();

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            $this->assertTrue($errors->has('end_time'), 'Validation error for end_time is missing');
            $this->assertTrue($errors->has('count'), 'Validation error for count is missing');

            throw $e;
        }
    }

    public function test_validation_on_add_appointment_without_count_and_duration_with_builder()
    {
        $this->expectException(ValidationException::class);

        try {

            AppointmentFacade::setAgent($this->agent)
                ->setClient($this->client)
                ->startTime(now()->format('Y-m-d H:i'))
                ->save();

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            $this->assertTrue($errors->has('end_time'), 'Validation error for end_time is missing');

            throw $e;
        }
    }

    public function test_validation_on_add_appointment_without_start_time_with_builder()
    {
        $this->expectException(ValidationException::class);

        try {

            AppointmentFacade::setAgent($this->agent)
                ->setClient($this->client)
                ->save();

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            $this->assertTrue($errors->has('start_time'), 'Validation error for start_time is missing');

            throw $e;
        }
    }
}

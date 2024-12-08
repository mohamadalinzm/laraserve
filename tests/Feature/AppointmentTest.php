<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use Nzm\LaravelAppointment\Models\Appointment;
use Tests\TestModels\Agent;
use Tests\TestModels\Client;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;
    public function testCreateAppointment()
    {
        //Arrange
        $data = Appointment::factory()->make()->toArray();
        //Act
        $appointment = Appointment::query()->create($data);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', $data);
    }

    protected function generateAppointment(): array
    {
        // Generate a random start time within the next month
        $startTime = $this->faker->dateTimeBetween('now', '+1 month');

        // Assume duration is in minutes; adjust as needed
        $durationMinutes = $this->faker->numberBetween(30, 120);

        // Calculate end time based on start time and duration
        $endTime = (clone $startTime)->modify("+{$durationMinutes} minutes");

        $agent = Agent::query()->create(['name' => $this->faker->name]);
        $client = Client::query()->create(['name' => $this->faker->name]);

        return [
            'agentable_type' => Agent::class,
            'agentable_id' => $agent->id,

            'clientable_type' => Client::class,
            'clientable_id' => $client->id,

            'start_time' => $startTime->format('Y-m-d H:i'),
            'end_time' => $endTime->format('Y-m-d H:i')
        ];
    }

}

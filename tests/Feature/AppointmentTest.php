<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

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

}

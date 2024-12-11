<?php

namespace Nzm\Appointment\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nzm\Appointment\Models\Appointment;
use Nzm\Appointment\Tests\Traits\SetUpDatabase;
use Orchestra\Testbench\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase,SetUpDatabase;

    public function test_book_appointment()
    {
        //Arrange
        $data = $this->generateAppointment();
        unset($data['clientable_id']);
        unset($data['clientable_type']);
        //Act
        $appointment = $this->agent->agentAppointments()->create($data);
        $this->client->bookAppointment($appointment);
        $data['clientable_id'] = $this->client->id;
        $data['clientable_type'] = get_class($this->client);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', $data);
    }
}

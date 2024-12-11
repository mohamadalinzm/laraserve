<?php

namespace Nzm\Appointment\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nzm\Appointment\Models\Appointment;
use Nzm\Appointment\Tests\Traits\SetUpDatabase;
use Orchestra\Testbench\TestCase;

class AgentTest extends TestCase
{
    use RefreshDatabase,SetUpDatabase;

    public function test_create_appointment_through_agent()
    {
        //Arrange
        $data = $this->generateAppointment();
        //Act
        $appointment = $this->agent->agentAppointments()->create($data);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', $data);
    }

    public function test_appointment_without_client()
    {
        //Arrange
        $data = $this->generateAppointment();

        unset($data['agentable_id']);
        unset($data['agentable_type']);
        unset($data['clientable_id']);
        unset($data['clientable_type']);

        //Act
        $appointment = $this->agent->agentAppointments()->create($data);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', $data);

    }

    public function test_get_available_slots()
    {
        //Arrange
        $data = $this->generateAppointment();

        unset($data['agentable_id']);
        unset($data['agentable_type']);
        unset($data['clientable_id']);
        unset($data['clientable_type']);

        $this->agent->agentAppointments()->create($data);
        //Act
        $availableSlots = $this->agent->getAvailableSlots();
        //Assert
        $this->assertCount(1, $availableSlots);
    }

    public function test_get_booked_slots()
    {
        //Arrange
        $data = $this->generateAppointment();

        unset($data['agentable_id']);
        unset($data['agentable_type']);

        $this->agent->agentAppointments()->create($data);
        //Act
        $bookedSlots = $this->agent->getBookedSlots();
        //Assert
        $this->assertCount(1, $bookedSlots);
    }

    public function test_get_upcoming_booked_slots()
    {
        //Arrange
        $data = $this->generateAppointment();

        unset($data['agentable_id']);
        unset($data['agentable_type']);

        $this->agent->agentAppointments()->create($data);
        //Act
        $upcomingBookedSlots = $this->agent->getUpComingBookedSlots();
        //Assert
        $this->assertCount(1, $upcomingBookedSlots);
    }
}

<?php

namespace Nzm\Appointment\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nzm\Appointment\Facades\AppointmentFacade;
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
        $bookedSlots = $this->agent->getAgentBookedSlots();
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
        $upcomingBookedSlots = $this->agent->getAgentUpcomingBookedSlots();
        //Assert
        $this->assertCount(1, $upcomingBookedSlots);
    }

    public function test_get_slots_by_date()
    {
        //Arrange
        $start_time = now()->addDay();
        $count = 5;
        $duration = 30;
        //Act
        $data = AppointmentFacade::setAgent($this->agent)
            ->startTime($start_time->format('Y-m-d H:i'))
            ->count($count)
            ->duration($duration)
            ->note('Test')
            ->save();
        $slots = $this->agent->getSlotsByDate($start_time->toDateString());
        //Assert
        $this->assertCount($count, $data);
        $this->assertCount($count, $slots);
    }

    public function test_find_slot_by_date()
    {
        //Arrange
        $start_time = now()->addDay();
        $count = 5;
        $duration = 30;
        //Act
        AppointmentFacade::setAgent($this->agent)
            ->startTime($start_time->format('Y-m-d H:i'))
            ->count($count)
            ->duration($duration)
            ->note('Test')
            ->save();
        $slot = $this->agent->findSlotByDate($start_time->format('Y-m-d H:i'));
        //Assert
        $this->assertInstanceOf(Appointment::class, $slot);
        $this->assertEquals($start_time->format('Y-m-d H:i'), $slot->start_time);
    }
}

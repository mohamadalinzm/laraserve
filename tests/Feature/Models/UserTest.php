<?php

namespace Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nzm\Appointment\Exceptions\AppointmentAlreadyBookedException;
use Nzm\Appointment\Exceptions\ExpiredAppointmentException;
use Nzm\Appointment\Exceptions\UnauthorizedAppointmentCancellationException;
use Nzm\Appointment\Facades\AppointmentFacade;
use Nzm\Appointment\Models\Appointment;
use Nzm\Appointment\Tests\TestModels\Client;
use Nzm\Appointment\Tests\TestModels\User;
use Nzm\Appointment\Tests\Traits\SetUpDatabase;
use Orchestra\Testbench\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase,SetUpDatabase;

    public function test_client_user_book_appointment()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent)
        ];
        //Act
        $appointment = $this->userAgent->agentAppointments()->create($data);
        $this->userClient->bookAppointment($appointment);
        $data['clientable_id'] = $this->userClient->id;
        $data['clientable_type'] = get_class($this->userClient);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', $data);
    }

    public function test_client_user_book_appointment_that_already_booked()
    {
        $this->expectException(AppointmentAlreadyBookedException::class);
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient)
        ];
        //Act
        $appointment = $this->userAgent->agentAppointments()->create($data);
        try {

            $this->client->bookAppointment($appointment);

        } catch (AppointmentAlreadyBookedException $e) {
            //Assert
            $this->assertEquals('Appointment is already booked by another client.', $e->getMessage());

            throw $e;
        }
    }

    public function test_client_user_book_appointment_that_in_the_past()
    {
        $this->expectException(ExpiredAppointmentException::class);
        //Arrange
        $data = [
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
        ];
        //Act
        $appointment = $this->userAgent->agentAppointments()->create($data);
        try {

            $this->userClient->bookAppointment($appointment);

        } catch (ExpiredAppointmentException $e) {
            //Assert
            $this->assertEquals('Appointments in the past cannot be booked or cancelled.', $e->getMessage());

            throw $e;
        }
    }

    public function test_get_appointments_of_client_user()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient)
        ];
        //Act
        $appointment = $this->userAgent->agentAppointments()->create($data);
        $appointment2 = $this->agent->agentAppointments()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
        ]);
        $appointment3 = $this->userAgent->agentAppointments()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
        ]);
        $data['agentable_id'] = $this->userAgent->id;
        $data['agentable_type'] = get_class($this->userAgent);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertInstanceOf(Appointment::class, $appointment2);
        $this->assertInstanceOf(Appointment::class, $appointment3);
        $this->assertDatabaseHas('appointments', $data);
        $this->assertDatabaseHas('appointments', [
            'start_time' => $appointment2->start_time,
            'end_time' => $appointment2->end_time,
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent),
        ]);
        $this->assertDatabaseHas('appointments', [
            'start_time' => $appointment3->start_time,
            'end_time' => $appointment3->end_time,
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ]);
        $this->assertCount(3, $this->userClient->clientAppointments);
    }

    public function test_client_user_see_only_booked_appointments()
    {
        //Arrange
        $newClient = User::query()->create(['name' => 'new user client','role' => 'client']);
        $data = [
            'start_time' => now()->addDays(4),
            'end_time' => now()->addDays(4)->addHour()
        ];
        //Act
        $appointment = $this->userAgent->agentAppointments()->create($data);
        $appointment2 = $this->userAgent->agentAppointments()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
        ]);
        $newAppointment = $this->userAgent->agentAppointments()->create([
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHour(),
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
        ]);
        $this->userClient->bookAppointment($appointment);
        $data['clientable_id'] = $this->userClient->id;
        $data['clientable_type'] = get_class($this->userClient);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertInstanceOf(Appointment::class, $appointment2);
        $this->assertInstanceOf(Appointment::class, $newAppointment);
        $this->assertDatabaseHas('appointments', $data);
        $this->assertDatabaseHas('appointments', [
            'start_time' => $appointment2->start_time,
            'end_time' => $appointment2->end_time,
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ]);
        $this->assertDatabaseHas('appointments', [
            'start_time' => $newAppointment->start_time,
            'end_time' => $newAppointment->end_time,
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ]);
        $this->assertCount(2, $this->userClient->getClientBookedSlots());
    }

    public function test_client_user_see_only_upcoming_booked_appointments()
    {
        //Arrange
        $newClient = User::query()->create(['name' => 'new client','role' => 'client']);
        $data = [
            'start_time' => now()->addDays(4),
            'end_time' => now()->addDays(4)->addHour()
        ];
        //Act
        $appointment = $this->userAgent->agentAppointments()->create($data);
        $appointment2 = $this->userAgent->agentAppointments()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
        ]);
        $newAppointment = $this->userAgent->agentAppointments()->create([
            'start_time' => now()->subDays(3),
            'end_time' => now()->subDays(3)->addHour(),
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
        ]);
        $this->userClient->bookAppointment($appointment);
        $data['clientable_id'] = $this->userClient->id;
        $data['clientable_type'] = get_class($this->userClient);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertInstanceOf(Appointment::class, $appointment2);
        $this->assertInstanceOf(Appointment::class, $newAppointment);
        $this->assertDatabaseHas('appointments', $data);
        $this->assertDatabaseHas('appointments', [
            'start_time' => $appointment2->start_time,
            'end_time' => $appointment2->end_time,
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ]);
        $this->assertDatabaseHas('appointments', [
            'start_time' => $newAppointment->start_time,
            'end_time' => $newAppointment->end_time,
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ]);
        $this->assertCount(1, $this->userClient->getClientUpcomingBookedSlots());
    }

    public function test_client_user_create_appointment()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent)
        ];
        //Act
        $appointment = $this->userClient->clientAppointments()->create($data);
        $data['clientable_id'] = $this->userClient->id;
        $data['clientable_type'] = get_class($this->userClient);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', $data);
    }

    public function test_client_user_cancel_appointment()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent)
        ];
        //Act
        $appointment = $this->userClient->clientAppointments()->create($data);
        $this->userClient->cancelAppointment($appointment);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', [
            'start_time' => $appointment->start_time,
            'end_time' => $appointment->end_time,
            'clientable_id' => null,
            'clientable_type' => null,
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ]);
    }

    public function test_client_user_cancel_appointment_that_not_booked()
    {
        $this->expectException(UnauthorizedAppointmentCancellationException::class);
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour()
        ];
        //Act
        $appointment = $this->userAgent->agentAppointments()->create($data);
        try {

            $this->userClient->cancelAppointment($appointment);

        } catch (UnauthorizedAppointmentCancellationException $e) {
            //Assert
            $this->assertEquals('You are not authorized to cancel this appointment.', $e->getMessage());

            throw $e;
        }
    }

    public function test_client_user_cancel_appointment_that_booked_by_another_client()
    {
        $this->expectException(UnauthorizedAppointmentCancellationException::class);
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent)
        ];
        //Act
        $appointment = $this->userClient->clientAppointments()->create($data);
        try {

            $this->client->cancelAppointment($appointment);

        } catch (UnauthorizedAppointmentCancellationException $e) {
            //Assert
            $this->assertEquals('You are not authorized to cancel this appointment.', $e->getMessage());

            throw $e;
        }
    }

    public function test_client_user_cancel_appointment_that_in_the_past()
    {
        $this->expectException(ExpiredAppointmentException::class);
        //Arrange
        $data = [
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ];
        $appointment = $this->userClient->clientAppointments()->create($data);
        //Act
        try {
            $this->userClient->cancelAppointment($appointment);
        } catch (ExpiredAppointmentException $e) {
            //Assert
            $this->assertEquals('Appointments in the past cannot be booked or cancelled.', $e->getMessage());

            throw $e;
        }
    }

    public function test_create_appointment_through_user_agent()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient)
        ];
        //Act
        $appointment = $this->userAgent->agentAppointments()->create($data);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', $data);
    }

    public function test_appointment_without_user_client()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour()
        ];
        //Act
        $appointment = $this->userAgent->agentAppointments()->create($data);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', $data);

    }

    public function test_get_available_slots_for_user_agent()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour()
        ];

        $this->userAgent->agentAppointments()->create($data);
        //Act
        $availableSlots = $this->userAgent->getAvailableSlots();
        //Assert
        $this->assertCount(1, $availableSlots);
    }

    public function test_get_booked_slots_for_user_agent()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient)
        ];

        $this->userAgent->agentAppointments()->create($data);
        //Act
        $bookedSlots = $this->userAgent->getAgentBookedSlots();
        //Assert
        $this->assertCount(1, $bookedSlots);
    }

    public function test_get_upcoming_booked_slots_for_user_agent()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient)
        ];

        $this->userAgent->agentAppointments()->create($data);
        //Act
        $upcomingBookedSlots = $this->userAgent->getAgentUpcomingBookedSlots();
        //Assert
        $this->assertCount(1, $upcomingBookedSlots);
    }

    public function test_get_slots_by_date_for_user_agent()
    {
        //Arrange
        $start_time = now()->addDay();
        $count = 5;
        $duration = 30;
        //Act
        $data = AppointmentFacade::setAgent($this->userAgent)
            ->startTime($start_time->format('Y-m-d H:i'))
            ->count($count)
            ->duration($duration)
            ->note('Test')
            ->save();
        $slots = $this->userAgent->getSlotsByDate($start_time->toDateString());
        //Assert
        $this->assertCount($count, $data);
        $this->assertCount($count, $slots);
    }
}

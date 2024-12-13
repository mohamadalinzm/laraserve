<?php

namespace Nzm\Appointment\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nzm\Appointment\Exceptions\AppointmentAlreadyBookedException;
use Nzm\Appointment\Exceptions\ExpiredAppointmentException;
use Nzm\Appointment\Exceptions\UnauthorizedAppointmentCancellationException;
use Nzm\Appointment\Models\Appointment;
use Nzm\Appointment\Tests\TestModels\Client;
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

    public function test_book_appointment_that_already_booked()
    {
        $this->expectException(AppointmentAlreadyBookedException::class);
        //Arrange
        $data = $this->generateAppointment();
        //Act
        $appointment = $this->agent->agentAppointments()->create($data);
        $this->client->bookAppointment($appointment);
        $newClient = Client::query()->create(['name' => 'new client']);
        try {

            $newClient->bookAppointment($appointment);

        } catch (AppointmentAlreadyBookedException $e) {
            //Assert
            $this->assertEquals('Appointment is already booked by another client.', $e->getMessage());

            throw $e;
        }
    }

    public function test_book_appointment_that_in_the_past()
    {
        $this->expectException(ExpiredAppointmentException::class);
        //Arrange
        $data = [
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour()
        ];
        //Act
        $appointment = $this->agent->agentAppointments()->create($data);
        try {

            $this->client->bookAppointment($appointment);

        } catch (ExpiredAppointmentException $e) {
            //Assert
            $this->assertEquals('Appointments in the past cannot be booked or cancelled.', $e->getMessage());

            throw $e;
        }
    }

    public function test_get_appointments_of_client()
    {
        //Arrange
        $data = $this->generateAppointment();
        //Act
        $appointment = $this->agent->agentAppointments()->create($data);
        $appointment2 = $this->agent->agentAppointments()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
        ]);
        $appointment3 = $this->agent->agentAppointments()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
        ]);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertInstanceOf(Appointment::class, $appointment2);
        $this->assertInstanceOf(Appointment::class, $appointment3);
        $this->assertDatabaseHas('appointments', $data);
        $this->assertDatabaseHas('appointments',[
            'start_time' => $appointment2->start_time,
            'end_time' => $appointment2->end_time,
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent)
        ]);
        $this->assertDatabaseHas('appointments',[
            'start_time' => $appointment3->start_time,
            'end_time' => $appointment3->end_time,
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent)
        ]);
        $this->assertCount(3, $this->client->clientAppointments);
    }

    public function test_client_see_only_booked_appointments()
    {
        //Arrange
        $newClient = Client::query()->create(['name' => 'new client']);
        $data = $this->generateAppointment();
        unset($data['clientable_id']);
        unset($data['clientable_type']);
        //Act
        $appointment = $this->agent->agentAppointments()->create($data);
        $appointment2 = $this->agent->agentAppointments()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
        ]);
        $newAppointment = $this->agent->agentAppointments()->create([
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHour(),
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
        ]);
        $this->client->bookAppointment($appointment);
        $data['clientable_id'] = $this->client->id;
        $data['clientable_type'] = get_class($this->client);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertInstanceOf(Appointment::class, $appointment2);
        $this->assertInstanceOf(Appointment::class, $newAppointment);
        $this->assertDatabaseHas('appointments', $data);
        $this->assertDatabaseHas('appointments', [
            'start_time' => $appointment2->start_time,
            'end_time' => $appointment2->end_time,
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent)
        ]);
        $this->assertDatabaseHas('appointments', [
            'start_time' => $newAppointment->start_time,
            'end_time' => $newAppointment->end_time,
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent)
        ]);
        $this->assertCount(2, $this->client->getBookedSlots());
    }

    public function test_client_see_only_upcoming_booked_appointments()
    {
        //Arrange
        $newClient = Client::query()->create(['name' => 'new client']);
        $data = $this->generateAppointment();
        unset($data['clientable_id']);
        unset($data['clientable_type']);
        //Act
        $appointment = $this->agent->agentAppointments()->create($data);
        $appointment2 = $this->agent->agentAppointments()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
        ]);
        $newAppointment = $this->agent->agentAppointments()->create([
            'start_time' => now()->subDays(3),
            'end_time' => now()->subDays(3)->addHour(),
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
        ]);
        $this->client->bookAppointment($appointment);
        $data['clientable_id'] = $this->client->id;
        $data['clientable_type'] = get_class($this->client);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertInstanceOf(Appointment::class, $appointment2);
        $this->assertInstanceOf(Appointment::class, $newAppointment);
        $this->assertDatabaseHas('appointments', $data);
        $this->assertDatabaseHas('appointments', [
            'start_time' => $appointment2->start_time,
            'end_time' => $appointment2->end_time,
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent)
        ]);
        $this->assertDatabaseHas('appointments', [
            'start_time' => $newAppointment->start_time,
            'end_time' => $newAppointment->end_time,
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent)
        ]);
        $this->assertCount(1, $this->client->getUpComingBookedSlots());
    }

    public function test_client_create_appointment()
    {
        //Arrange
        $data = $this->generateAppointment();
        unset($data['clientable_id']);
        unset($data['clientable_type']);
        //Act
        $appointment = $this->client->clientAppointments()->create($data);
        $data['clientable_id'] = $this->client->id;
        $data['clientable_type'] = get_class($this->client);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', $data);
    }

    public function test_client_cancel_appointment()
    {
        //Arrange
        $data = $this->generateAppointment();
        //Act
        $appointment = $this->client->clientAppointments()->create($data);
        $this->client->cancelAppointment($appointment);
        //Assert
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertDatabaseHas('appointments', [
            'start_time' => $appointment->start_time,
            'end_time' => $appointment->end_time,
            'clientable_id' => null,
            'clientable_type' => null,
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent)
        ]);
    }

    public function test_client_cancel_appointment_that_not_booked()
    {
        $this->expectException(UnauthorizedAppointmentCancellationException::class);
        //Arrange
        $data = $this->generateAppointment();
        unset($data['clientable_id']);
        unset($data['clientable_type']);
        //Act
        $appointment = $this->agent->agentAppointments()->create($data);
        try {

            $this->client->cancelAppointment($appointment);

        } catch (UnauthorizedAppointmentCancellationException $e) {
            //Assert
            $this->assertEquals('You are not authorized to cancel this appointment.', $e->getMessage());

            throw $e;
        }
    }

    public function test_client_cancel_appointment_that_booked_by_another_client()
    {
        $this->expectException(UnauthorizedAppointmentCancellationException::class);
        //Arrange
        $data = $this->generateAppointment();
        //Act
        $appointment = $this->client->clientAppointments()->create($data);
        $newClient = Client::query()->create(['name' => 'new client']);
        try {

            $newClient->cancelAppointment($appointment);

        } catch (UnauthorizedAppointmentCancellationException $e) {
            //Assert
            $this->assertEquals('You are not authorized to cancel this appointment.', $e->getMessage());

            throw $e;
        }
    }

    public function test_client_cancel_appointment_that_in_the_past()
    {
        $this->expectException(ExpiredAppointmentException::class);
        //Arrange
        $data = [
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent)
        ];
        $appointment = $this->client->clientAppointments()->create($data);
        //Act
        try {
            $this->client->cancelAppointment($appointment);
        } catch (ExpiredAppointmentException $e) {
            //Assert
            $this->assertEquals('Appointments in the past cannot be booked or cancelled.', $e->getMessage());

            throw $e;
        }
    }
}

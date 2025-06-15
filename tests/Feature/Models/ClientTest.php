<?php

namespace Nazemi\Laraserve\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nazemi\Laraserve\Exceptions\ReservationAlreadyBookedException;
use Nazemi\Laraserve\Exceptions\ExpiredReservationException;
use Nazemi\Laraserve\Exceptions\UnauthorizedReservationCancellationException;
use Nazemi\Laraserve\Models\Reservation;
use Nazemi\Laraserve\Tests\TestModels\Client;
use Nazemi\Laraserve\Tests\Traits\SetUpDatabase;
use Orchestra\Testbench\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase,SetUpDatabase;

    public function test_book_reservation()
    {
        //Arrange
        $data = $this->generateReservation();
        unset($data['clientable_id']);
        unset($data['clientable_type']);
        //Act
        $reservation = $this->agent->agentReservations()->create($data);
        $this->client->bookReservation($reservation);
        $data['clientable_id'] = $this->client->id;
        $data['clientable_type'] = get_class($this->client);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);
    }

    public function test_book_reservation_that_already_booked()
    {
        $this->expectException(ReservationAlreadyBookedException::class);
        //Arrange
        $data = $this->generateReservation();
        //Act
        $reservation = $this->agent->agentReservations()->create($data);
        $this->client->bookReservation($reservation);
        $newClient = Client::query()->create(['name' => 'new client']);
        try {

            $newClient->bookReservation($reservation);

        } catch (ReservationAlreadyBookedException $e) {
            //Assert
            $this->assertEquals('Reservation is already booked by another client.', $e->getMessage());

            throw $e;
        }
    }

    public function test_book_reservation_that_in_the_past()
    {
        $this->expectException(ExpiredReservationException::class);
        //Arrange
        $data = [
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
        ];
        //Act
        $reservation = $this->agent->agentReservations()->create($data);
        try {

            $this->client->bookReservation($reservation);

        } catch (ExpiredReservationException $e) {
            //Assert
            $this->assertEquals('Reservations in the past cannot be booked or cancelled.', $e->getMessage());

            throw $e;
        }
    }

    public function test_get_reservations_of_client()
    {
        //Arrange
        $data = $this->generateReservation();
        //Act
        $reservation = $this->agent->agentReservations()->create($data);
        $reservation2 = $this->agent->agentReservations()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
        ]);
        $reservation3 = $this->agent->agentReservations()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
        ]);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $reservation3);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation3->start_time,
            'end_time' => $reservation3->end_time,
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent),
        ]);
        $this->assertCount(3, $this->client->clientReservations);
    }

    public function test_client_see_only_booked_reservations()
    {
        //Arrange
        $newClient = Client::query()->create(['name' => 'new client']);
        $data = $this->generateReservation();
        unset($data['clientable_id']);
        unset($data['clientable_type']);
        //Act
        $reservation = $this->agent->agentReservations()->create($data);
        $reservation2 = $this->agent->agentReservations()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
        ]);
        $newReservation = $this->agent->agentReservations()->create([
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHour(),
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
        ]);
        $this->client->bookReservation($reservation);
        $data['clientable_id'] = $this->client->id;
        $data['clientable_type'] = get_class($this->client);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $newReservation);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $newReservation->start_time,
            'end_time' => $newReservation->end_time,
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent),
        ]);
        $this->assertCount(2, $this->client->getClientBookedSlots());
    }

    public function test_client_see_only_upcoming_booked_reservations()
    {
        //Arrange
        $newClient = Client::query()->create(['name' => 'new client']);
        $data = $this->generateReservation();
        unset($data['clientable_id']);
        unset($data['clientable_type']);
        //Act
        $reservation = $this->agent->agentReservations()->create($data);
        $reservation2 = $this->agent->agentReservations()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
        ]);
        $newReservation = $this->agent->agentReservations()->create([
            'start_time' => now()->subDays(3),
            'end_time' => now()->subDays(3)->addHour(),
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
        ]);
        $this->client->bookReservation($reservation);
        $data['clientable_id'] = $this->client->id;
        $data['clientable_type'] = get_class($this->client);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $newReservation);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'clientable_id' => $this->client->id,
            'clientable_type' => get_class($this->client),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $newReservation->start_time,
            'end_time' => $newReservation->end_time,
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent),
        ]);
        $this->assertCount(1, $this->client->getClientUpcomingBookedSlots());
    }

    public function test_client_create_reservation()
    {
        //Arrange
        $data = $this->generateReservation();
        unset($data['clientable_id']);
        unset($data['clientable_type']);
        //Act
        $reservation = $this->client->clientReservations()->create($data);
        $data['clientable_id'] = $this->client->id;
        $data['clientable_type'] = get_class($this->client);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);
    }

    public function test_client_cancel_reservation()
    {
        //Arrange
        $data = $this->generateReservation();
        //Act
        $reservation = $this->client->clientReservations()->create($data);
        $this->client->cancelReservation($reservation);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'clientable_id' => null,
            'clientable_type' => null,
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent),
        ]);
    }

    public function test_client_cancel_reservation_that_not_booked()
    {
        $this->expectException(UnauthorizedReservationCancellationException::class);
        //Arrange
        $data = $this->generateReservation();
        unset($data['clientable_id']);
        unset($data['clientable_type']);
        //Act
        $reservation = $this->agent->agentReservations()->create($data);
        try {

            $this->client->cancelReservation($reservation);

        } catch (UnauthorizedReservationCancellationException $e) {
            //Assert
            $this->assertEquals('You are not authorized to cancel this reservation.', $e->getMessage());

            throw $e;
        }
    }

    public function test_client_cancel_reservation_that_booked_by_another_client()
    {
        $this->expectException(UnauthorizedReservationCancellationException::class);
        //Arrange
        $data = $this->generateReservation();
        //Act
        $reservation = $this->client->clientReservations()->create($data);
        $newClient = Client::query()->create(['name' => 'new client']);
        try {

            $newClient->cancelReservation($reservation);

        } catch (UnauthorizedReservationCancellationException $e) {
            //Assert
            $this->assertEquals('You are not authorized to cancel this reservation.', $e->getMessage());

            throw $e;
        }
    }

    public function test_client_cancel_reservation_that_in_the_past()
    {
        $this->expectException(ExpiredReservationException::class);
        //Arrange
        $data = [
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent),
        ];
        $reservation = $this->client->clientReservations()->create($data);
        //Act
        try {
            $this->client->cancelReservation($reservation);
        } catch (ExpiredReservationException $e) {
            //Assert
            $this->assertEquals('Reservations in the past cannot be booked or cancelled.', $e->getMessage());

            throw $e;
        }
    }
}

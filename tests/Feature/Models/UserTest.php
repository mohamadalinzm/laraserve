<?php

namespace Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nazemi\Laraserve\Exceptions\ReservationAlreadyBookedException;
use Nazemi\Laraserve\Exceptions\ExpiredReservationException;
use Nazemi\Laraserve\Exceptions\UnauthorizedReservationCancellationException;
use Nazemi\Laraserve\Facades\ReservationFacade;
use Nazemi\Laraserve\Models\Reservation;
use Nazemi\Laraserve\Tests\TestModels\Client;
use Nazemi\Laraserve\Tests\TestModels\User;
use Nazemi\Laraserve\Tests\Traits\SetUpDatabase;
use Orchestra\Testbench\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase,SetUpDatabase;

    public function test_client_user_book_reservation()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent)
        ];
        //Act
        $reservation = $this->userAgent->agentReservations()->create($data);
        $this->userClient->bookReservation($reservation);
        $data['clientable_id'] = $this->userClient->id;
        $data['clientable_type'] = get_class($this->userClient);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);
    }

    public function test_client_user_book_reservation_that_already_booked()
    {
        $this->expectException(ReservationAlreadyBookedException::class);
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient)
        ];
        //Act
        $reservation = $this->userAgent->agentReservations()->create($data);
        try {

            $this->client->bookReservation($reservation);

        } catch (ReservationAlreadyBookedException $e) {
            //Assert
            $this->assertEquals('Reservation is already booked by another client.', $e->getMessage());

            throw $e;
        }
    }

    public function test_client_user_book_reservation_that_in_the_past()
    {
        $this->expectException(ExpiredReservationException::class);
        //Arrange
        $data = [
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
        ];
        //Act
        $reservation = $this->userAgent->agentReservations()->create($data);
        try {

            $this->userClient->bookReservation($reservation);

        } catch (ExpiredReservationException $e) {
            //Assert
            $this->assertEquals('Reservations in the past cannot be booked or cancelled.', $e->getMessage());

            throw $e;
        }
    }

    public function test_get_reservations_of_client_user()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient)
        ];
        //Act
        $reservation = $this->userAgent->agentReservations()->create($data);
        $reservation2 = $this->agent->agentReservations()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
        ]);
        $reservation3 = $this->userAgent->agentReservations()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
        ]);
        $data['agentable_id'] = $this->userAgent->id;
        $data['agentable_type'] = get_class($this->userAgent);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $reservation3);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
            'agentable_id' => $this->agent->id,
            'agentable_type' => get_class($this->agent),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation3->start_time,
            'end_time' => $reservation3->end_time,
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ]);
        $this->assertCount(3, $this->userClient->clientReservations);
    }

    public function test_client_user_see_only_booked_reservations()
    {
        //Arrange
        $newClient = User::query()->create(['name' => 'new user client','role' => 'client']);
        $data = [
            'start_time' => now()->addDays(4),
            'end_time' => now()->addDays(4)->addHour()
        ];
        //Act
        $reservation = $this->userAgent->agentReservations()->create($data);
        $reservation2 = $this->userAgent->agentReservations()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
        ]);
        $newReservation = $this->userAgent->agentReservations()->create([
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHour(),
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
        ]);
        $this->userClient->bookReservation($reservation);
        $data['clientable_id'] = $this->userClient->id;
        $data['clientable_type'] = get_class($this->userClient);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $newReservation);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $newReservation->start_time,
            'end_time' => $newReservation->end_time,
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ]);
        $this->assertCount(2, $this->userClient->getClientBookedSlots());
    }

    public function test_client_user_see_only_upcoming_booked_reservations()
    {
        //Arrange
        $newClient = User::query()->create(['name' => 'new client','role' => 'client']);
        $data = [
            'start_time' => now()->addDays(4),
            'end_time' => now()->addDays(4)->addHour()
        ];
        //Act
        $reservation = $this->userAgent->agentReservations()->create($data);
        $reservation2 = $this->userAgent->agentReservations()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
        ]);
        $newReservation = $this->userAgent->agentReservations()->create([
            'start_time' => now()->subDays(3),
            'end_time' => now()->subDays(3)->addHour(),
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
        ]);
        $this->userClient->bookReservation($reservation);
        $data['clientable_id'] = $this->userClient->id;
        $data['clientable_type'] = get_class($this->userClient);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $newReservation);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $newReservation->start_time,
            'end_time' => $newReservation->end_time,
            'clientable_id' => $newClient->id,
            'clientable_type' => get_class($newClient),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ]);
        $this->assertCount(1, $this->userClient->getClientUpcomingBookedSlots());
    }

    public function test_client_user_create_reservation()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent)
        ];
        //Act
        $reservation = $this->userClient->clientReservations()->create($data);
        $data['clientable_id'] = $this->userClient->id;
        $data['clientable_type'] = get_class($this->userClient);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);
    }

    public function test_client_user_cancel_reservation()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent)
        ];
        //Act
        $reservation = $this->userClient->clientReservations()->create($data);
        $this->userClient->cancelReservation($reservation);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'clientable_id' => null,
            'clientable_type' => null,
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ]);
    }

    public function test_client_user_cancel_reservation_that_not_booked()
    {
        $this->expectException(UnauthorizedReservationCancellationException::class);
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour()
        ];
        //Act
        $reservation = $this->userAgent->agentReservations()->create($data);
        try {

            $this->userClient->cancelReservation($reservation);

        } catch (UnauthorizedReservationCancellationException $e) {
            //Assert
            $this->assertEquals('You are not authorized to cancel this reservation.', $e->getMessage());

            throw $e;
        }
    }

    public function test_client_user_cancel_reservation_that_booked_by_another_client()
    {
        $this->expectException(UnauthorizedReservationCancellationException::class);
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent)
        ];
        //Act
        $reservation = $this->userClient->clientReservations()->create($data);
        try {

            $this->client->cancelReservation($reservation);

        } catch (UnauthorizedReservationCancellationException $e) {
            //Assert
            $this->assertEquals('You are not authorized to cancel this reservation.', $e->getMessage());

            throw $e;
        }
    }

    public function test_client_user_cancel_reservation_that_in_the_past()
    {
        $this->expectException(ExpiredReservationException::class);
        //Arrange
        $data = [
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'agentable_id' => $this->userAgent->id,
            'agentable_type' => get_class($this->userAgent),
        ];
        $reservation = $this->userClient->clientReservations()->create($data);
        //Act
        try {
            $this->userClient->cancelReservation($reservation);
        } catch (ExpiredReservationException $e) {
            //Assert
            $this->assertEquals('Reservations in the past cannot be booked or cancelled.', $e->getMessage());

            throw $e;
        }
    }

    public function test_create_reservation_through_user_agent()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'clientable_id' => $this->userClient->id,
            'clientable_type' => get_class($this->userClient)
        ];
        //Act
        $reservation = $this->userAgent->agentReservations()->create($data);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);
    }

    public function test_reservation_without_user_client()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour()
        ];
        //Act
        $reservation = $this->userAgent->agentReservations()->create($data);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);

    }

    public function test_get_available_slots_for_user_agent()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour()
        ];

        $this->userAgent->agentReservations()->create($data);
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

        $this->userAgent->agentReservations()->create($data);
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

        $this->userAgent->agentReservations()->create($data);
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
        $data = ReservationFacade::setAgent($this->userAgent)
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

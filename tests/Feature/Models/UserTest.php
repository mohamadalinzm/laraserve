<?php

namespace Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nazemi\Laraserve\Exceptions\ReservationAlreadyBookedException;
use Nazemi\Laraserve\Exceptions\ExpiredReservationException;
use Nazemi\Laraserve\Exceptions\UnauthorizedReservationCancellationException;
use Nazemi\Laraserve\Facades\ReservationFacade;
use Nazemi\Laraserve\Models\Reservation;
use Nazemi\Laraserve\Tests\TestModels\Recipient;
use Nazemi\Laraserve\Tests\TestModels\User;
use Nazemi\Laraserve\Tests\Traits\SetUpDatabase;
use Orchestra\Testbench\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase,SetUpDatabase;

    public function test_recipient_user_book_reservation()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'provider_id' => $this->providerUser->id,
            'provider_type' => get_class($this->providerUser)
        ];
        //Act
        $reservation = $this->providerUser->providedReservations()->create($data);
        $this->recipientUser->reserve($reservation);
        $data['recipient_id'] = $this->recipientUser->id;
        $data['recipient_type'] = get_class($this->recipientUser);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);
    }

    public function test_recipient_user_book_reservation_that_already_booked()
    {
        $this->expectException(ReservationAlreadyBookedException::class);
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser)
        ];
        //Act
        $reservation = $this->providerUser->providedReservations()->create($data);
        try {

            $this->recipient->reserve($reservation);

        } catch (ReservationAlreadyBookedException $e) {
            //Assert
            $this->assertEquals('Reservation is already booked by another recipient.', $e->getMessage());

            throw $e;
        }
    }

    public function test_recipient_user_book_reservation_that_in_the_past()
    {
        $this->expectException(ExpiredReservationException::class);
        //Arrange
        $data = [
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
        ];
        //Act
        $reservation = $this->providerUser->providedReservations()->create($data);
        try {

            $this->recipientUser->reserve($reservation);

        } catch (ExpiredReservationException $e) {
            //Assert
            $this->assertEquals('Reservations in the past cannot be booked or cancelled.', $e->getMessage());

            throw $e;
        }
    }

    public function test_get_reservations_of_recipient_user()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHour(),
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser)
        ];
        //Act
        $reservation = $this->providerUser->providedReservations()->create($data);
        $reservation2 = $this->provider->providedReservations()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser),
        ]);
        $reservation3 = $this->providerUser->providedReservations()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser),
        ]);
        $data['provider_id'] = $this->providerUser->id;
        $data['provider_type'] = get_class($this->providerUser);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $reservation3);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser),
            'provider_id' => $this->provider->id,
            'provider_type' => get_class($this->provider),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation3->start_time,
            'end_time' => $reservation3->end_time,
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser),
            'provider_id' => $this->providerUser->id,
            'provider_type' => get_class($this->providerUser),
        ]);
        $this->assertCount(3, $this->recipientUser->receivedReservations);
    }

    public function test_recipient_user_see_only_booked_reservations()
    {
        //Arrange
        $newRecipient = User::query()->create(['name' => 'new user recipient','role' => 'recipient']);
        $data = [
            'start_time' => now()->addDays(4),
            'end_time' => now()->addDays(4)->addHour()
        ];
        //Act
        $reservation = $this->providerUser->providedReservations()->create($data);
        $reservation2 = $this->providerUser->providedReservations()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser),
        ]);
        $newReservation = $this->providerUser->providedReservations()->create([
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHour(),
            'recipient_id' => $newRecipient->id,
            'recipient_type' => get_class($newRecipient),
        ]);
        $this->recipientUser->reserve($reservation);
        $data['recipient_id'] = $this->recipientUser->id;
        $data['recipient_type'] = get_class($this->recipientUser);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $newReservation);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser),
            'provider_id' => $this->providerUser->id,
            'provider_type' => get_class($this->providerUser),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $newReservation->start_time,
            'end_time' => $newReservation->end_time,
            'recipient_id' => $newRecipient->id,
            'recipient_type' => get_class($newRecipient),
            'provider_id' => $this->providerUser->id,
            'provider_type' => get_class($this->providerUser),
        ]);
        $this->assertCount(2, $this->recipientUser->receivedReservations()->get());
    }

    public function test_recipient_user_see_only_upcoming_booked_reservations()
    {
        //Arrange
        $newRecipient = User::query()->create(['name' => 'new recipient','role' => 'recipient']);
        $data = [
            'start_time' => now()->addDays(4),
            'end_time' => now()->addDays(4)->addHour()
        ];
        //Act
        $reservation = $this->providerUser->providedReservations()->create($data);
        $reservation2 = $this->providerUser->providedReservations()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser),
        ]);
        $newReservation = $this->providerUser->providedReservations()->create([
            'start_time' => now()->subDays(3),
            'end_time' => now()->subDays(3)->addHour(),
            'recipient_id' => $newRecipient->id,
            'recipient_type' => get_class($newRecipient),
        ]);
        $this->recipientUser->reserve($reservation);
        $data['recipient_id'] = $this->recipientUser->id;
        $data['recipient_type'] = get_class($this->recipientUser);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $newReservation);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser),
            'provider_id' => $this->providerUser->id,
            'provider_type' => get_class($this->providerUser),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $newReservation->start_time,
            'end_time' => $newReservation->end_time,
            'recipient_id' => $newRecipient->id,
            'recipient_type' => get_class($newRecipient),
            'provider_id' => $this->providerUser->id,
            'provider_type' => get_class($this->providerUser),
        ]);
        $this->assertCount(2, $this->recipientUser->receivedReservations()->get());
    }

    public function test_recipient_user_create_reservation()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'provider_id' => $this->providerUser->id,
            'provider_type' => get_class($this->providerUser)
        ];
        //Act
        $reservation = $this->recipientUser->receivedReservations()->create($data);
        $data['recipient_id'] = $this->recipientUser->id;
        $data['recipient_type'] = get_class($this->recipientUser);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);
    }

    public function test_recipient_user_cancel_reservation()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'provider_id' => $this->providerUser->id,
            'provider_type' => get_class($this->providerUser)
        ];
        //Act
        $reservation = $this->recipientUser->receivedReservations()->create($data);
        $this->recipientUser->cancel($reservation);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'recipient_id' => null,
            'recipient_type' => null,
            'provider_id' => $this->providerUser->id,
            'provider_type' => get_class($this->providerUser),
        ]);
    }

    public function test_recipient_user_cancel_reservation_that_not_booked()
    {
        $this->expectException(UnauthorizedReservationCancellationException::class);
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour()
        ];
        //Act
        $reservation = $this->providerUser->providedReservations()->create($data);
        try {

            $this->recipientUser->cancel($reservation);

        } catch (UnauthorizedReservationCancellationException $e) {
            //Assert
            $this->assertEquals('You are not authorized to cancel this reservation.', $e->getMessage());

            throw $e;
        }
    }

    public function test_recipient_user_cancel_reservation_that_booked_by_another_recipient()
    {
        $this->expectException(UnauthorizedReservationCancellationException::class);
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'provider_id' => $this->providerUser->id,
            'provider_type' => get_class($this->providerUser)
        ];
        //Act
        $reservation = $this->recipientUser->receivedReservations()->create($data);
        try {

            $this->recipient->cancel($reservation);

        } catch (UnauthorizedReservationCancellationException $e) {
            //Assert
            $this->assertEquals('You are not authorized to cancel this reservation.', $e->getMessage());

            throw $e;
        }
    }

    public function test_recipient_user_cancel_reservation_that_in_the_past()
    {
        $this->expectException(ExpiredReservationException::class);
        //Arrange
        $data = [
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'provider_id' => $this->providerUser->id,
            'provider_type' => get_class($this->providerUser),
        ];
        $reservation = $this->recipientUser->receivedReservations()->create($data);
        //Act
        try {
            $this->recipientUser->cancel($reservation);
        } catch (ExpiredReservationException $e) {
            //Assert
            $this->assertEquals('Reservations in the past cannot be booked or cancelled.', $e->getMessage());

            throw $e;
        }
    }

    public function test_create_reservation_through_user_provider()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser)
        ];
        //Act
        $reservation = $this->providerUser->providedReservations()->create($data);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);
    }

    public function test_reservation_without_user_recipient()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour()
        ];
        //Act
        $reservation = $this->providerUser->providedReservations()->create($data);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);

    }

    public function test_get_available_slots_for_user_provider()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour()
        ];

        $this->providerUser->providedReservations()->create($data);
        //Act
        $availableSlots = $this->providerUser->getAvailableSlots();
        //Assert
        $this->assertCount(1, $availableSlots);
    }

    public function test_get_booked_slots_for_user_provider()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser)
        ];

        $this->providerUser->providedReservations()->create($data);
        //Act
        $bookedSlots = $this->providerUser->getBookedSlots();
        //Assert
        $this->assertCount(1, $bookedSlots);
    }

    public function test_get_upcoming_booked_slots_for_user_provider()
    {
        //Arrange
        $data = [
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'recipient_id' => $this->recipientUser->id,
            'recipient_type' => get_class($this->recipientUser)
        ];

        $this->providerUser->providedReservations()->create($data);
        //Act
        $upcomingBookedSlots = $this->providerUser->getUpcomingBookedSlots();
        //Assert
        $this->assertCount(1, $upcomingBookedSlots);
    }

    public function test_get_slots_by_date_for_user_provider()
    {
        //Arrange
        $start_time = now()->addDay();
        $count = 5;
        $duration = 30;
        //Act
        $data = ReservationFacade::setProvider($this->providerUser)
            ->startTime($start_time->format('Y-m-d H:i'))
            ->count($count)
            ->duration($duration)
            ->note('Test')
            ->save();
        $slots = $this->providerUser->getSlotsByDate($start_time->toDateString());
        //Assert
        $this->assertCount($count, $data);
        $this->assertCount($count, $slots);
    }
}

<?php

namespace Nazemi\Laraserve\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nazemi\Laraserve\Exceptions\ReservationAlreadyBookedException;
use Nazemi\Laraserve\Exceptions\ExpiredReservationException;
use Nazemi\Laraserve\Exceptions\UnauthorizedReservationCancellationException;
use Nazemi\Laraserve\Models\Reservation;
use Nazemi\Laraserve\Tests\TestModels\Recipient;
use Nazemi\Laraserve\Tests\Traits\SetUpDatabase;
use Orchestra\Testbench\TestCase;

class RecipientTest extends TestCase
{
    use RefreshDatabase,SetUpDatabase;

    public function test_book_reservation()
    {
        //Arrange
        $data = $this->generateReservation();
        unset($data['recipient_id']);
        unset($data['recipient_type']);
        //Act
        $reservation = $this->provider->providedReservations()->create($data);
        $this->recipient->reserve($reservation);
        $data['recipient_id'] = $this->recipient->id;
        $data['recipient_type'] = get_class($this->recipient);
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
        $reservation = $this->provider->providedReservations()->create($data);
        $this->recipient->reserve($reservation);
        $newRecipient = Recipient::query()->create(['name' => 'new recipient']);
        try {

            $newRecipient->reserve($reservation);

        } catch (ReservationAlreadyBookedException $e) {
            //Assert
            $this->assertEquals('Reservation is already booked by another recipient.', $e->getMessage());

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
        $reservation = $this->provider->providedReservations()->create($data);
        try {

            $this->recipient->reserve($reservation);

        } catch (ExpiredReservationException $e) {
            //Assert
            $this->assertEquals('Reservations in the past cannot be booked or cancelled.', $e->getMessage());

            throw $e;
        }
    }

    public function test_get_reservations_of_recipient()
    {
        //Arrange
        $data = $this->generateReservation();
        //Act
        $reservation = $this->provider->providedReservations()->create($data);
        $reservation2 = $this->provider->providedReservations()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'recipient_id' => $this->recipient->id,
            'recipient_type' => get_class($this->recipient),
        ]);
        $reservation3 = $this->provider->providedReservations()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'recipient_id' => $this->recipient->id,
            'recipient_type' => get_class($this->recipient),
        ]);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $reservation3);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'recipient_id' => $this->recipient->id,
            'recipient_type' => get_class($this->recipient),
            'provider_id' => $this->provider->id,
            'provider_type' => get_class($this->provider),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation3->start_time,
            'end_time' => $reservation3->end_time,
            'recipient_id' => $this->recipient->id,
            'recipient_type' => get_class($this->recipient),
            'provider_id' => $this->provider->id,
            'provider_type' => get_class($this->provider),
        ]);
        $this->assertCount(3, $this->recipient->receivedReservations);
    }

    public function test_recipient_see_only_booked_reservations()
    {
        //Arrange
        $newRecipient = Recipient::query()->create(['name' => 'new recipient']);
        $data = $this->generateReservation();
        unset($data['recipient_id']);
        unset($data['recipient_type']);
        //Act
        $reservation = $this->provider->providedReservations()->create($data);
        $reservation2 = $this->provider->providedReservations()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'recipient_id' => $this->recipient->id,
            'recipient_type' => get_class($this->recipient),
        ]);
        $newReservation = $this->provider->providedReservations()->create([
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHour(),
            'recipient_id' => $newRecipient->id,
            'recipient_type' => get_class($newRecipient),
        ]);
        $this->recipient->reserve($reservation);
        $data['recipient_id'] = $this->recipient->id;
        $data['recipient_type'] = get_class($this->recipient);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $newReservation);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'recipient_id' => $this->recipient->id,
            'recipient_type' => get_class($this->recipient),
            'provider_id' => $this->provider->id,
            'provider_type' => get_class($this->provider),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $newReservation->start_time,
            'end_time' => $newReservation->end_time,
            'recipient_id' => $newRecipient->id,
            'recipient_type' => get_class($newRecipient),
            'provider_id' => $this->provider->id,
            'provider_type' => get_class($this->provider),
        ]);
        $this->assertCount(2, $this->recipient->receivedReservations()->get());
    }

    public function test_recipient_see_only_upcoming_booked_reservations()
    {
        //Arrange
        $newRecipient = Recipient::query()->create(['name' => 'new recipient']);
        $data = $this->generateReservation();
        unset($data['recipient_id']);
        unset($data['recipient_type']);
        //Act
        $reservation = $this->provider->providedReservations()->create($data);
        $reservation2 = $this->provider->providedReservations()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'recipient_id' => $this->recipient->id,
            'recipient_type' => get_class($this->recipient),
        ]);
        $newReservation = $this->provider->providedReservations()->create([
            'start_time' => now()->subDays(3),
            'end_time' => now()->subDays(3)->addHour(),
            'recipient_id' => $newRecipient->id,
            'recipient_type' => get_class($newRecipient),
        ]);
        $this->recipient->reserve($reservation);
        $data['recipient_id'] = $this->recipient->id;
        $data['recipient_type'] = get_class($this->recipient);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $newReservation);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'recipient_id' => $this->recipient->id,
            'recipient_type' => get_class($this->recipient),
            'provider_id' => $this->provider->id,
            'provider_type' => get_class($this->provider),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $newReservation->start_time,
            'end_time' => $newReservation->end_time,
            'recipient_id' => $newRecipient->id,
            'recipient_type' => get_class($newRecipient),
            'provider_id' => $this->provider->id,
            'provider_type' => get_class($this->provider),
        ]);
        $this->assertCount(2, $this->recipient->receivedReservations()->get());
        $this->assertCount(1, $newRecipient->receivedReservations()->get());
    }

    public function test_recipient_create_reservation()
    {
        //Arrange
        $data = $this->generateReservation();
        unset($data['recipient_id']);
        unset($data['recipient_type']);
        //Act
        $reservation = $this->recipient->receivedReservations()->create($data);
        $data['recipient_id'] = $this->recipient->id;
        $data['recipient_type'] = get_class($this->recipient);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);
    }

    public function test_recipient_cancel_reservation()
    {
        //Arrange
        $data = $this->generateReservation();
        //Act
        $reservation = $this->recipient->receivedReservations()->create($data);
        $this->recipient->cancel($reservation);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'recipient_id' => null,
            'recipient_type' => null,
            'provider_id' => $this->provider->id,
            'provider_type' => get_class($this->provider),
        ]);
    }

    public function test_recipient_cancel_reservation_that_not_booked()
    {
        $this->expectException(UnauthorizedReservationCancellationException::class);
        //Arrange
        $data = $this->generateReservation();
        unset($data['recipient_id']);
        unset($data['recipient_type']);
        //Act
        $reservation = $this->provider->providedReservations()->create($data);
        try {

            $this->recipient->cancel($reservation);

        } catch (UnauthorizedReservationCancellationException $e) {
            //Assert
            $this->assertEquals('You are not authorized to cancel this reservation.', $e->getMessage());

            throw $e;
        }
    }

    public function test_recipient_cancel_reservation_that_booked_by_another_recipient()
    {
        $this->expectException(UnauthorizedReservationCancellationException::class);
        //Arrange
        $data = $this->generateReservation();
        //Act
        $reservation = $this->recipient->receivedReservations()->create($data);
        $newRecipient = Recipient::query()->create(['name' => 'new recipient']);
        try {

            $newRecipient->cancel($reservation);

        } catch (UnauthorizedReservationCancellationException $e) {
            //Assert
            $this->assertEquals('You are not authorized to cancel this reservation.', $e->getMessage());

            throw $e;
        }
    }

    public function test_recipient_cancel_reservation_that_in_the_past()
    {
        $this->expectException(ExpiredReservationException::class);
        //Arrange
        $data = [
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'provider_id' => $this->provider->id,
            'provider_type' => get_class($this->provider),
        ];
        $reservation = $this->recipient->receivedReservations()->create($data);
        //Act
        try {
            $this->recipient->cancel($reservation);
        } catch (ExpiredReservationException $e) {
            //Assert
            $this->assertEquals('Reservations in the past cannot be booked or cancelled.', $e->getMessage());

            throw $e;
        }
    }

    public function test_recipient_get_upcoming_reservations()
    {
        //Arrange
        $data = $this->generateReservation();
        unset($data['recipient_id']);
        unset($data['recipient_type']);
        //Act
        $reservation = $this->provider->providedReservations()->create($data);
        $reservation2 = $this->provider->providedReservations()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'recipient_id' => $this->recipient->id,
            'recipient_type' => get_class($this->recipient),
        ]);
        $reservation3 = $this->provider->providedReservations()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHour(),
            'recipient_id' => $this->recipient->id,
            'recipient_type' => get_class($this->recipient),
        ]);
        $this->recipient->reserve($reservation);
        $data['recipient_id'] = $this->recipient->id;
        $data['recipient_type'] = get_class($this->recipient);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertInstanceOf(Reservation::class, $reservation3);
        $this->assertDatabaseHas('reservations', $data);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'recipient_id' => $this->recipient->id,
            'recipient_type' => get_class($this->recipient),
            'provider_id' => $this->provider->id,
            'provider_type' => get_class($this->provider),
        ]);
        $this->assertDatabaseHas('reservations', [
            'start_time' => $reservation3->start_time,
            'end_time' => $reservation3->end_time,
            'recipient_id' => $this->recipient->id,
            'recipient_type' => get_class($this->recipient),
            'provider_id' => $this->provider->id,
            'provider_type' => get_class($this->provider),
        ]);
        $this->assertCount(3, $this->recipient->receivedReservations()->get());
        $upcomingReservations = $this->recipient->getUpcomingReservations();
        $this->assertCount(2, $upcomingReservations);
        $this->assertTrue($upcomingReservations->contains($reservation2));
        $this->assertFalse($upcomingReservations->contains($reservation3));
    }
}

<?php

namespace Nazemi\Laraserve\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Nazemi\Laraserve\Facades\Laraserve;
use Nazemi\Laraserve\Models\Reservation;
use Nazemi\Laraserve\Tests\Traits\SetUpDatabase;
use Orchestra\Testbench\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase,SetUpDatabase;

    public function test_create_reservation()
    {
        //Arrange
        $data = $this->generateReservation();
        //Act
        $reservation = Reservation::query()->create($data);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);
    }

    public function test_provider_relation()
    {
        $data = $this->generateReservation();
        $reservation = Reservation::query()->create($data);
        $this->assertInstanceOf($this->provider::class, $reservation->provider);
        $this->assertEquals($this->provider->id, $reservation->provider->id);
    }

    public function test_recipient_relation()
    {
        $data = $this->generateReservation();
        $reservation = Reservation::query()->create($data);
        $this->assertInstanceOf($this->recipient::class, $reservation->recipient);
        $this->assertEquals($this->recipient->id, $reservation->recipient->id);
    }

    public function test_create_reservation_with_note()
    {
        //Arrange
        $data = $this->generateReservation();
        $data['note'] = 'This is a note';
        //Act
        $reservation = Reservation::query()->create($data);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);
    }

    public function test_create_reservation_via_facade()
    {
        $reservation = Laraserve::setProvider($this->provider)
            ->setRecipient($this->recipient)
            ->startTime(now()->format('Y-m-d H:i'))
            ->endTime(now()->addMinutes(30)->format('Y-m-d H:i'))
            ->save();

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', [
            'provider_type' => get_class($this->provider),
            'provider_id' => $this->provider->id,
            'recipient_type' => get_class($this->recipient),
            'recipient_id' => $this->recipient->id,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
        ]);
    }

    public function test_create_reservation_with_note_via_facade()
    {
        //Arrange
        $note = 'This is a note';
        //Act
        $reservation = Laraserve::setProvider($this->provider)
            ->setRecipient($this->recipient)
            ->startTime(now()->format('Y-m-d H:i'))
            ->endTime(now()->addMinutes(30)->format('Y-m-d H:i'))
            ->note($note)
            ->save();
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', [
            'provider_type' => get_class($this->provider),
            'provider_id' => $this->provider->id,
            'recipient_type' => get_class($this->recipient),
            'recipient_id' => $this->recipient->id,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'note' => $note,
        ]);
    }

    public function test_create_reservation_via_count_and_duration_via_facade()
    {
        $duration = 30;
        $count = 3;
        $reservations = Laraserve::setProvider($this->provider)
            ->startTime(now()->format('Y-m-d H:i'))
            ->duration($duration)
            ->count($count)
            ->save();

        $this->assertIsArray($reservations);
        $this->assertCount($count, $reservations);

        foreach ($reservations as $reservation) {
            $this->assertInstanceOf(Reservation::class, $reservation);
            $this->assertDatabaseHas('reservations', [
                'provider_type' => get_class($this->provider),
                'provider_id' => $this->provider->id,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
            ]);
        }
    }

    public function test_validation_on_add_reservation_via_count_and_without_duration_via_facade()
    {
        $this->expectException(ValidationException::class);

        try {

            Laraserve::setProvider($this->provider)
                ->setRecipient($this->recipient)
                ->startTime(now()->format('Y-m-d H:i'))
                ->count(3)
                ->save();

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            $this->assertTrue($errors->has('end_time'), 'The end time field is required when duration / count is not present.');
            $this->assertTrue($errors->has('duration'), 'The duration field is required.');

            throw $e;
        }
    }

    public function test_validation_on_add_reservation_via_duration_and_without_count_via_facade()
    {
        $this->expectException(ValidationException::class);

        try {

            Laraserve::setProvider($this->provider)
                ->setRecipient($this->recipient)
                ->startTime(now()->format('Y-m-d H:i'))
                ->duration(30)
                ->save();

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            $this->assertTrue($errors->has('end_time'), 'The end time field is required when duration / count is not present.');
            $this->assertTrue($errors->has('count'), 'The count field is required.');

            throw $e;
        }
    }

    public function test_validation_on_add_reservation_without_count_and_duration_via_facade()
    {
        $this->expectException(ValidationException::class);

        try {

            Laraserve::setProvider($this->provider)
                ->setRecipient($this->recipient)
                ->startTime(now()->format('Y-m-d H:i'))
                ->save();

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            $this->assertTrue($errors->has('end_time'), 'The end time field is required when duration / count is not present.');

            throw $e;
        }
    }

    public function test_validation_on_add_reservation_without_start_time_via_facade()
    {
        $this->expectException(ValidationException::class);

        try {

            Laraserve::setProvider($this->provider)
                ->setRecipient($this->recipient)
                ->save();

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            $this->assertTrue($errors->has('start_time'), 'The start time field is required.');

            throw $e;
        }
    }

    public function test_validation_on_add_reservation_with_overlap_via_facade()
    {
        $this->expectException(ValidationException::class);

        try {

            $data = $this->generateReservation();
            $data['start_time'] = now()->format('Y-m-d H:i');
            $data['end_time'] = now()->addMinutes(30)->format('Y-m-d H:i');

            $this->provider->providedReservations()->create($data);

            Laraserve::setProvider($this->provider)
                ->setRecipient($this->recipient)
                ->startTime(now()->addMinutes(10)->format('Y-m-d H:i'))
                ->endTime(now()->addMinutes(40)->format('Y-m-d H:i'))
                ->save();

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            $this->assertTrue($errors->has('start_time'), 'This reservation conflicts with an existing reservation time.');

            throw $e;
        }
    }

    public function test_Add_reservation_when_config_overlap_is_true_via_facade()
    {
        $this->expectException(ValidationException::class);

        config(['laraserve.overlap' => true]);
        $data = $this->generateReservation();
        $data['start_time'] = now()->format('Y-m-d H:i');
        $data['end_time'] = now()->addMinutes(30)->format('Y-m-d H:i');

        $reservation = $this->provider->providedReservations()->create($data);

        $reservation2 = Laraserve::setProvider($this->provider)
            ->setRecipient($this->recipient)
            ->startTime(now()->addMinutes(10)->format('Y-m-d H:i'))
            ->endTime(now()->addMinutes(40)->format('Y-m-d H:i'))
            ->save();

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertInstanceOf(Reservation::class, $reservation2);
        $this->assertDatabaseHas('reservations', [
            'provider_type' => get_class($this->provider),
            'provider_id' => $this->provider->id,
            'recipient_type' => get_class($this->recipient),
            'recipient_id' => $this->recipient->id,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
        ]);
        $this->assertDatabaseHas('reservations', [
            'provider_type' => get_class($this->provider),
            'provider_id' => $this->provider->id,
            'recipient_type' => get_class($this->recipient),
            'recipient_id' => $this->recipient->id,
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
        ]);
    }
}

<?php

namespace Nazemi\Laraserve\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Nazemi\Laraserve\Facades\ReservationFacade;
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
        $reservation = ReservationFacade::setAgent($this->agent)
            ->setClient($this->client)
            ->startTime(now()->format('Y-m-d H:i'))
            ->endTime(now()->addMinutes(30)->format('Y-m-d H:i'))
            ->save();

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', [
            'agentable_type' => get_class($this->agent),
            'agentable_id' => $this->agent->id,
            'clientable_type' => get_class($this->client),
            'clientable_id' => $this->client->id,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
        ]);
    }

    public function test_create_reservation_with_note_via_facade()
    {
        //Arrange
        $note = 'This is a note';
        //Act
        $reservation = ReservationFacade::setAgent($this->agent)
            ->setClient($this->client)
            ->startTime(now()->format('Y-m-d H:i'))
            ->endTime(now()->addMinutes(30)->format('Y-m-d H:i'))
            ->note($note)
            ->save();
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', [
            'agentable_type' => get_class($this->agent),
            'agentable_id' => $this->agent->id,
            'clientable_type' => get_class($this->client),
            'clientable_id' => $this->client->id,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'note' => $note,
        ]);
    }

    public function test_create_reservation_via_count_and_duration_via_facade()
    {
        $duration = 30;
        $count = 3;
        $reservations = ReservationFacade::setAgent($this->agent)
            ->startTime(now()->format('Y-m-d H:i'))
            ->duration($duration)
            ->count($count)
            ->save();

        $this->assertIsArray($reservations);
        $this->assertCount($count, $reservations);

        foreach ($reservations as $reservation) {
            $this->assertInstanceOf(Reservation::class, $reservation);
            $this->assertDatabaseHas('reservations', [
                'agentable_type' => get_class($this->agent),
                'agentable_id' => $this->agent->id,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
            ]);
        }
    }

    public function test_validation_on_add_reservation_via_count_and_without_duration_via_facade()
    {
        $this->expectException(ValidationException::class);

        try {

            ReservationFacade::setAgent($this->agent)
                ->setClient($this->client)
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

            ReservationFacade::setAgent($this->agent)
                ->setClient($this->client)
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

            ReservationFacade::setAgent($this->agent)
                ->setClient($this->client)
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

            ReservationFacade::setAgent($this->agent)
                ->setClient($this->client)
                ->save();

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            $this->assertTrue($errors->has('start_time'), 'The start time field is required.');

            throw $e;
        }
    }

    //add test for overlap validation
    public function test_validation_on_add_reservation_with_overlap_via_facade()
    {
        $this->expectException(ValidationException::class);

        try {

            $data = $this->generateReservation();
            $data['start_time'] = now()->format('Y-m-d H:i');
            $data['end_time'] = now()->addMinutes(30)->format('Y-m-d H:i');

            $this->agent->agentReservations()->create($data);

            ReservationFacade::setAgent($this->agent)
                ->setClient($this->client)
                ->startTime(now()->addMinutes(10)->format('Y-m-d H:i'))
                ->endTime(now()->addMinutes(40)->format('Y-m-d H:i'))
                ->save();

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            $this->assertTrue($errors->has('start_time'), 'This reservation conflicts with an existing reservation time.');

            throw $e;
        }
    }
}

<?php

namespace Nazemi\Laraserve\Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nazemi\Laraserve\Facades\Laraserve;
use Nazemi\Laraserve\Models\Reservation;
use Nazemi\Laraserve\Tests\Traits\SetUpDatabase;
use Orchestra\Testbench\TestCase;

class ProviderTest extends TestCase
{
    use RefreshDatabase,SetUpDatabase;

    public function test_create_reservation_through_provider()
    {
        //Arrange
        $data = $this->generateReservation();
        //Act
        $reservation = $this->provider->providedReservations()->create($data);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);
    }

    public function test_reservation_without_recipient()
    {
        //Arrange
        $data = $this->generateReservation();

        unset($data['provider_id']);
        unset($data['provider_type']);
        unset($data['recipient_id']);
        unset($data['recipient_type']);

        //Act
        $reservation = $this->provider->providedReservations()->create($data);
        //Assert
        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertDatabaseHas('reservations', $data);

    }

    public function test_get_available_slots()
    {
        //Arrange
        $data = $this->generateReservation();

        unset($data['provider_id']);
        unset($data['provider_type']);
        unset($data['recipient_id']);
        unset($data['recipient_type']);

        $this->provider->providedReservations()->create($data);
        //Act
        $availableSlots = $this->provider->getAvailableSlots();
        //Assert
        $this->assertCount(1, $availableSlots);
    }

    public function test_get_booked_slots()
    {
        //Arrange
        $data = $this->generateReservation();

        unset($data['provider_id']);
        unset($data['provider_type']);

        $this->provider->providedReservations()->create($data);
        //Act
        $bookedSlots = $this->provider->getBookedSlots();
        //Assert
        $this->assertCount(1, $bookedSlots);
    }

    public function test_get_upcoming_booked_slots()
    {
        //Arrange
        $data = $this->generateReservation();

        unset($data['provider_id']);
        unset($data['provider_type']);

        $this->provider->providedReservations()->create($data);
        //Act
        $upcomingBookedSlots = $this->provider->getUpcomingBookedSlots();
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
        $data = Laraserve::setProvider($this->provider)
            ->startTime($start_time->format('Y-m-d H:i'))
            ->count($count)
            ->duration($duration)
            ->note('Test')
            ->save();
        $slots = $this->provider->getSlotsByDate($start_time->toDateString());
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
        Laraserve::setProvider($this->provider)
            ->startTime($start_time->format('Y-m-d H:i'))
            ->count($count)
            ->duration($duration)
            ->note('Test')
            ->save();
        $slot = $this->provider->findSlotByDate($start_time->format('Y-m-d H:i'));
        //Assert
        $this->assertInstanceOf(Reservation::class, $slot);
        $this->assertEquals($start_time->format('Y-m-d H:i'), $slot->start_time);
    }
}

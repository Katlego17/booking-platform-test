<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Actions\CheckBookingOverlap;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and a client
        $this->user = User::factory()->create();
        $this->client = Client::factory()->create();
    }

    /** @test */
    public function index_displays_bookings_and_clients()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id
        ]);

        $response = $this->actingAs($this->user)->get(route('bookings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('bookings');
        $response->assertViewHas('bookings');
        $response->assertViewHas('clients');
    }

    /** @test */
    public function get_bookings_by_weeks_returns_paginated_results()
    {
        // Create a booking that falls within this week
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'start_time' => Carbon::now()->startOfWeek()->addDay(),
            'end_time' => Carbon::now()->startOfWeek()->addDays(2),
        ]);

        // Create 50 more bookings, some in this week, some outside
        Booking::factory(50)->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'start_time' => function () {
                // Random day +/- 7 days from now
                return Carbon::now()->startOfWeek()->addDays(rand(0, 6))->addHours(rand(0, 23));
            },
            'end_time' => function ($attrs) {
                return Carbon::parse($attrs['start_time'])->addHour();
            },
        ]);

        $week = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->user)->get(route('bookings.byWeeks', ['week' => $week]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'links', 'meta']);

        // Optional: check that at least 3 bookings fall in the week
        $bookingsInWeek = collect($response->json('data'))->count();
        $this->assertTrue($bookingsInWeek >= 3, "Expected at least 3 bookings in the week, found $bookingsInWeek");
    }

    /** @test */
    public function store_creates_booking_successfully()
    {
        $data = [
            'title' => 'Test Booking',
            'description' => 'Description here',
            'start_time' => now()->addHour()->toDateTimeString(),
            'end_time' => now()->addHours(2)->toDateTimeString(),
            'client_id' => $this->client->id,
        ];

        // Mock CheckBookingOverlap to return false (no overlap)
        $this->mock(CheckBookingOverlap::class, function ($mock) use ($data) {
            $mock->shouldReceive('handle')->with($data)->andReturn(false);
        });

        $response = $this->actingAs($this->user)->post(route('booking.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'title' => 'Test Booking',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function store_prevents_overlapping_booking()
    {
        $data = [
            'title' => 'Overlap Test',
            'description' => 'Desc',
            'start_time' => now()->addHour()->toDateTimeString(),
            'end_time' => now()->addHours(2)->toDateTimeString(),
            'client_id' => $this->client->id,
        ];

        Booking::create([
            'title' => 'Existing Booking',
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('booking.store'), $data);

        // Assert booking was NOT saved
        $this->assertDatabaseMissing('bookings', [
            'title' => 'Overlap Test',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function update_modifies_booking_successfully()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id
        ]);

        $data = [
            'title' => 'Updated Title',
            'description' => 'Updated Desc',
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDays(2)->toDateTimeString(),
            'client_id' => $this->client->id
        ];

        // Mock CheckBookingOverlap to return false (no overlap)
        $this->mock(CheckBookingOverlap::class, function ($mock) use ($data, $booking) {
            $mock->shouldReceive('handle')->with($data, $booking->id)->andReturn(false);
        });

        $response = $this->actingAs($this->user)->put(route('bookings.update', $booking->id), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'title' => 'Updated Title'
        ]);
    }

    /** @test */
    public function update_prevents_overlapping_booking()
    {
        $existingBooking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(2),
        ]);

        $bookingToUpdate = Booking::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addHours(1),
            'end_time' => now()->addHours(2),
        ]);

        $data = [
            'title' => 'Updated Overlap',
            'description' => 'Desc',
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDays(2)->toDateTimeString(),
            'client_id' => $this->client->id
        ];

        $response = $this->actingAs($this->user)->put(route('bookings.update', $bookingToUpdate->id), $data);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingToUpdate->id,
            'title' => $bookingToUpdate->title,
        ]);
    }

    /** @test */
    public function destroy_deletes_booking_successfully()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id
        ]);

        $response = $this->actingAs($this->user)->delete(route('booking.deletion', $booking->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }
}

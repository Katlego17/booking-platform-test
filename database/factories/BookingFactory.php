<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition()
    {
        $start = Carbon::now()->addDays(rand(1, 5));
        $end   = (clone $start)->addHours(rand(1, 3));

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(8),
            'start_time' => $start,
            'end_time' => $end,
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Location;
use App\Models\Professional;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDay()->setTime(10, 0);

        return [
            'client_id' => Client::factory(),
            'professional_id' => Professional::factory(),
            'service_id' => Service::factory(),
            'location_id' => fn () => Location::current()->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
            'price' => 15000,
            'status' => BookingStatus::Reservado,
            'payment_status' => PaymentStatus::SinPagar,
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function at(string $datetime, int $minutes = 60): static
    {
        return $this->state(function () use ($datetime, $minutes) {
            $start = \Carbon\Carbon::parse($datetime);

            return ['start_at' => $start, 'end_at' => $start->copy()->addMinutes($minutes)];
        });
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}

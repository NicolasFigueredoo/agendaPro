<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'amount' => 5000,
            'method' => PaymentMethod::Efectivo,
            'mp_payment_id' => null,
            'notes' => null,
            'created_by' => null,
            'paid_at' => now(),
        ];
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\MpPaymentLink;
use Illuminate\Console\Command;

/**
 * Cancela las reservas online que no pagaron la seña a tiempo y libera el horario.
 * Corre cada minuto desde el scheduler (routes/console.php).
 */
class ReleaseExpiredBookings extends Command
{
    protected $signature = 'bookings:release-expired';

    protected $description = 'Cancela las reservas online cuya seña no se pagó a tiempo';

    public function handle(): int
    {
        $count = 0;

        Booking::query()
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->where('payment_status', PaymentStatus::SinPagar->value)
            ->where('status', '!=', BookingStatus::Cancelado->value)
            ->each(function (Booking $booking) use (&$count) {
                $booking->changeStatus(BookingStatus::Cancelado);
                $booking->update(['hold_expires_at' => null]);

                $booking->paymentLinks()
                    ->where('status', MpPaymentLink::STATUS_PENDING)
                    ->update(['status' => MpPaymentLink::STATUS_EXPIRED]);

                $count++;
            });

        $this->info("Reservas liberadas: {$count}");

        return self::SUCCESS;
    }
}

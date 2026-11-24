<?php

namespace App\Livewire\Booking;

use App\Models\MpPaymentLink;
use App\Services\BookingPaymentService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Página pública de retorno de Mercado Pago: /reserva/{token}?resultado=exito|pendiente|error
 * Muestra el estado de la reserva y se actualiza sola hasta que llega la confirmación del pago.
 */
#[Layout('layouts.public')]
#[Title('Tu reserva')]
class Status extends Component
{
    public string $token;

    #[Url]
    public string $resultado = '';

    public function mount(string $token, BookingPaymentService $payments): void
    {
        $this->token = $token;

        $link = MpPaymentLink::where('token', $token)->firstOrFail();

        // MP devuelve payment_id en el retorno: lo procesamos ya (no esperamos al webhook)
        $paymentId = request()->query('payment_id') ?? request()->query('collection_id');

        if ($paymentId && $paymentId !== 'null' && $link->status !== MpPaymentLink::STATUS_PAID) {
            try {
                $payments->processMercadoPagoPayment((string) $paymentId);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    public function render()
    {
        $link = MpPaymentLink::where('token', $this->token)
            ->with(['booking.service', 'booking.professional', 'booking.client'])
            ->firstOrFail();

        $booking = $link->booking;

        $state = match (true) {
            $link->status === MpPaymentLink::STATUS_PAID => 'confirmed',
            $booking->isAwaitingDeposit() && $booking->hold_expires_at->isFuture() => $this->resultado === 'error' || $link->status === MpPaymentLink::STATUS_REJECTED ? 'failed' : 'waiting',
            default => 'expired',
        };

        return view('livewire.booking.status', [
            'link' => $link,
            'booking' => $booking,
            'state' => $state,
        ]);
    }
}

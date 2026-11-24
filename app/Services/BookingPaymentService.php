<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\MpPaymentLink;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Registro de pagos de turnos y procesamiento de pagos de Mercado Pago.
 */
class BookingPaymentService
{
    public function __construct(
        protected MercadoPagoService $mercadoPago,
        protected Availability $availability,
    ) {
    }

    public function register(
        Booking $booking,
        int $amount,
        PaymentMethod $method,
        ?string $mpPaymentId = null,
        ?string $notes = null,
        ?int $userId = null,
    ): Payment {
        $payment = $booking->payments()->create([
            'amount' => $amount,
            'method' => $method,
            'mp_payment_id' => $mpPaymentId,
            'notes' => $notes,
            'created_by' => $userId,
            'paid_at' => now(),
        ]);

        $this->refreshPaymentStatus($booking);

        return $payment;
    }

    /** Sin pagar / Seña / Pagado según lo cobrado vs. el precio. */
    public function refreshPaymentStatus(Booking $booking): void
    {
        $paid = (int) $booking->payments()->sum('amount');

        $booking->payment_status = PaymentStatus::fromAmounts($booking->price, $paid);
        $booking->save();
    }

    /**
     * Procesa un pago de Mercado Pago (lo llama el webhook y la página de retorno).
     * Es idempotente: el mismo payment_id nunca genera dos pagos.
     *
     * Devuelve el pago registrado (nuevo o existente) o null si todavía no está aprobado.
     */
    public function processMercadoPagoPayment(string $paymentId): ?Payment
    {
        $existing = Payment::where('mp_payment_id', $paymentId)->first();

        if ($existing) {
            return $existing;
        }

        $data = $this->mercadoPago->getPayment($paymentId);
        $link = MpPaymentLink::find((int) $data['external_reference']);

        if (! $link) {
            Log::warning('Mercado Pago: pago sin link asociado', ['payment_id' => $paymentId, 'external_reference' => $data['external_reference']]);

            return null;
        }

        if ($data['status'] !== 'approved') {
            if ($link->status !== MpPaymentLink::STATUS_PAID) {
                $link->update([
                    'status' => in_array($data['status'], ['rejected', 'cancelled'], true)
                        ? MpPaymentLink::STATUS_REJECTED
                        : MpPaymentLink::STATUS_PENDING,
                    'raw_response' => $data['raw'],
                ]);
            }

            return null;
        }

        $created = false;

        $payment = DB::transaction(function () use ($link, $data, $paymentId, &$created) {
            $link = MpPaymentLink::whereKey($link->id)->lockForUpdate()->first();

            if ($payment = Payment::where('mp_payment_id', $paymentId)->first()) {
                return $payment;
            }

            /** @var Booking $booking */
            $booking = Booking::withTrashed()->whereKey($link->booking_id)->lockForUpdate()->first();

            $notes = $link->type === MpPaymentLink::TYPE_SENA ? 'Seña por Mercado Pago' : 'Pago total por Mercado Pago';

            // La retención venció y el turno se liberó antes de que llegue el pago
            if ($booking->status === BookingStatus::Cancelado || $booking->trashed()) {
                if (! $booking->trashed() && $this->availability->isAvailable($booking->professional, $booking->start_at, $booking->end_at, $booking->id)) {
                    $booking->changeStatus(BookingStatus::Reservado);
                } else {
                    $notes .= ' · ATENCIÓN: el turno estaba vencido u ocupado, revisar o devolver el pago';
                }
            }

            $payment = $booking->payments()->create([
                'amount' => (int) round($data['amount']),
                'method' => PaymentMethod::MercadoPago,
                'mp_payment_id' => $paymentId,
                'notes' => $notes,
                'created_by' => null,
                'paid_at' => now(),
            ]);

            $link->update([
                'status' => MpPaymentLink::STATUS_PAID,
                'mp_payment_id' => $paymentId,
                'raw_response' => $data['raw'],
            ]);

            $this->refreshPaymentStatus($booking);

            if ($booking->status === BookingStatus::Reservado) {
                $booking->changeStatus(BookingStatus::Confirmado);
            }

            $booking->hold_expires_at = null;
            $booking->save();

            $created = true;

            return $payment;
        });

        if ($created) {
            $booking = $payment->booking()->with(['client', 'service', 'professional'])->first();

            if ($booking->status === BookingStatus::Confirmado && $booking->client?->email) {
                Mail::to($booking->client->email)->queue(new BookingConfirmed($booking));
            }
        }

        return $payment;
    }
}

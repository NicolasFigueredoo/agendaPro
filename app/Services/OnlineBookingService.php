<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Location;
use App\Models\MpPaymentLink;
use App\Models\Professional;
use App\Models\Service;
use App\Support\Deposit;
use App\Support\Phone;
use App\Support\Settings;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Reserva online: el cliente elige servicio, profesional (o cualquiera) y horario,
 * se crea el turno retenido por X minutos y se genera el link de pago de la seña.
 */
class OnlineBookingService
{
    public function __construct(
        protected Availability $availability,
        protected MercadoPagoService $mercadoPago,
    ) {
    }

    /** Profesionales que aceptan reservas online y hacen el servicio. */
    public function professionalsFor(Service $service): Collection
    {
        return Professional::online()
            ->whereHas('services', fn ($q) => $q->whereKey($service->id))
            ->with(['schedules', 'breaks'])
            ->ordered()
            ->get();
    }

    /** Horario más temprano en que se puede reservar online. */
    public function earliestStart(): CarbonInterface
    {
        return now()->addHours(Settings::int('online.min_hours_ahead'));
    }

    public function lastDate(): CarbonInterface
    {
        return today()->addDays(Settings::int('online.max_days_ahead'));
    }

    /**
     * Horarios disponibles en un día: ["09:00" => [ids de profesionales libres], ...]
     *
     * @return array<string, array<int, int>>
     */
    public function slotsFor(Service $service, CarbonInterface $date, ?int $professionalId = null): array
    {
        if ($date->gt($this->lastDate()) || $date->lt(today())) {
            return [];
        }

        $step = Settings::int('agenda.slot_minutes') ?: 30;
        $slots = [];

        foreach ($this->professionalsFor($service) as $professional) {
            if ($professionalId && $professional->id !== $professionalId) {
                continue;
            }

            foreach ($this->availability->slots($professional, $date, $service->duration_minutes, $step, $this->earliestStart()) as $time) {
                $slots[$time][] = $professional->id;
            }
        }

        ksort($slots);

        return $slots;
    }

    /**
     * Crea la reserva retenida + el link de pago de la seña.
     *
     * @param  array{first_name: string, last_name: string, phone: string, email: string, notes?: string}  $clientData
     * @return array{booking: Booking, link: MpPaymentLink|null}
     *
     * @throws SlotUnavailableException
     */
    public function reserve(Service $service, ?int $professionalId, CarbonInterface $start, array $clientData): array
    {
        if ($start->lt($this->earliestStart()) || $start->gt($this->lastDate()->copy()->endOfDay())) {
            throw new SlotUnavailableException('Ese horario ya no se puede reservar online.');
        }

        $end = $start->copy()->addMinutes($service->duration_minutes);
        $holdMinutes = Settings::int('online.hold_minutes') ?: 30;
        $deposit = Deposit::amountFor($service->price);

        [$booking, $link] = DB::transaction(function () use ($service, $professionalId, $start, $end, $clientData, $holdMinutes, $deposit) {
            $candidates = $this->professionalsFor($service)
                ->when($professionalId, fn ($c) => $c->where('id', $professionalId))
                ->pluck('id');

            if ($candidates->isEmpty()) {
                throw new SlotUnavailableException('No hay profesionales disponibles para ese servicio.');
            }

            // Bloquea a los profesionales para que dos personas no tomen el mismo horario a la vez
            $professionals = Professional::whereIn('id', $candidates)
                ->with(['schedules', 'breaks'])
                ->lockForUpdate()
                ->get()
                ->sortBy(fn ($p) => $p->bookings()->occupying()->onDate($start)->count()); // el menos cargado primero

            $professional = $professionals->first(fn ($p) => $this->availability->isAvailable($p, $start, $end));

            if (! $professional) {
                throw new SlotUnavailableException('Ese horario se acaba de ocupar. Elegí otro, por favor.');
            }

            $client = $this->findOrCreateClient($clientData);

            if ($this->availability->clientIsBusy($client->id, $start, $end)) {
                throw new SlotUnavailableException('Ya tenés un turno en ese horario.');
            }

            $booking = Booking::create([
                'client_id' => $client->id,
                'professional_id' => $professional->id,
                'service_id' => $service->id,
                'location_id' => $professional->location_id ?? Location::current()->id,
                'start_at' => $start,
                'end_at' => $end,
                'price' => $service->price,
                'status' => BookingStatus::Reservado,
                'source' => Booking::SOURCE_ONLINE,
                'hold_expires_at' => $deposit > 0 ? now()->addMinutes($holdMinutes) : null,
                'notes' => trim(($clientData['notes'] ?? '') !== '' ? 'Reserva online: '.$clientData['notes'] : 'Reserva online'),
            ]);

            $booking->statusLogs()->create([
                'from_status' => null,
                'to_status' => BookingStatus::Reservado->value,
                'user_id' => null,
            ]);

            $link = $deposit > 0
                ? MpPaymentLink::create([
                    'booking_id' => $booking->id,
                    'type' => MpPaymentLink::TYPE_SENA,
                    'amount' => $deposit,
                    'status' => MpPaymentLink::STATUS_PENDING,
                    'expires_at' => $booking->hold_expires_at,
                ])
                : null;

            return [$booking, $link];
        });

        if ($link) {
            try {
                $preference = $this->mercadoPago->createPreference($link);
            } catch (Throwable $e) {
                $booking->changeStatus(BookingStatus::Cancelado);
                $link->update(['status' => MpPaymentLink::STATUS_CANCELLED]);

                throw $e;
            }

            $link->update([
                'preference_id' => $preference['id'],
                'init_point' => $preference['init_point'],
                'raw_response' => $preference['raw'],
            ]);
        }

        return ['booking' => $booking->fresh(), 'link' => $link?->fresh()];
    }

    /** Busca por teléfono o email; si no existe lo crea. Completa los datos que falten. */
    public function findOrCreateClient(array $data): Client
    {
        $phone = Phone::normalize($data['phone'] ?? null);
        $email = isset($data['email']) && $data['email'] !== '' ? mb_strtolower(trim($data['email'])) : null;

        $client = ! $phone && ! $email ? null : Client::query()
            ->where(function ($q) use ($phone, $email) {
                $q->when($phone, fn ($w) => $w->orWhere('phone', $phone))
                    ->when($email, fn ($w) => $w->orWhere('email', $email));
            })
            ->orderByRaw('CASE WHEN phone = ? THEN 0 ELSE 1 END', [$phone ?? ''])
            ->first();

        if ($client) {
            $client->fill(array_filter([
                'phone' => $client->phone ? null : $phone,
                'email' => $client->email ? null : $email,
            ]))->save();

            return $client;
        }

        return Client::create([
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'phone' => $phone,
            'email' => $email,
        ]);
    }
}

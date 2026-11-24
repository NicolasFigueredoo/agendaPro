<?php

namespace App\Livewire\Booking;

use App\Exceptions\SlotUnavailableException;
use App\Models\Professional;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\MercadoPagoService;
use App\Services\OnlineBookingService;
use App\Support\Deposit;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/**
 * Página pública /reservar: el cliente saca turno solo y paga la seña con Mercado Pago.
 * Pasos: 1 servicio · 2 profesional · 3 día y hora · 4 datos · 5 resumen y pago
 */
#[Layout('layouts.public')]
#[Title('Reservá tu turno')]
class PublicBooking extends Component
{
    public int $step = 1;

    public ?int $serviceId = null;

    public string $professionalId = 'any'; // 'any' o id

    public ?string $date = null;

    public ?string $time = null;

    public int $dayOffset = 0;

    public string $first_name = '';

    public string $last_name = '';

    public string $phone = '';

    public string $email = '';

    public string $notes = '';

    public bool $acceptTerms = false;

    public function selectService(int $id): void
    {
        $this->serviceId = $id;
        $this->reset(['professionalId', 'date', 'time', 'dayOffset']);

        $this->step = $this->professionals->count() > 1 ? 2 : 3;

        if ($this->professionals->count() === 1) {
            $this->professionalId = (string) $this->professionals->first()->id;
        }
    }

    public function selectProfessional(string $id): void
    {
        $this->professionalId = $id;
        $this->reset(['date', 'time', 'dayOffset']);
        $this->step = 3;
    }

    public function selectDate(string $date): void
    {
        $this->date = $date;
        $this->time = null;
    }

    public function moreDays(int $direction): void
    {
        $this->dayOffset = max(0, $this->dayOffset + ($direction * 7));
    }

    public function selectTime(string $time): void
    {
        $this->time = $time;
        $this->step = 4;
    }

    public function toSummary(): void
    {
        $this->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[\d\s\-\+\(\)]{8,}$/'],
            'email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'phone.regex' => 'Ingresá un teléfono válido (código de área + número).',
        ], [
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'phone' => 'teléfono',
            'notes' => 'comentarios',
        ]);

        $this->step = 5;
    }

    public function goTo(int $step): void
    {
        if ($step < $this->step) {
            $this->step = $step;
        }
    }

    public function confirm(OnlineBookingService $online)
    {
        $this->validate(['acceptTerms' => ['accepted']], ['acceptTerms.accepted' => 'Tenés que aceptar las condiciones de la seña.']);

        $key = 'online-booking:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('confirm', 'Hiciste muchos intentos seguidos. Esperá unos minutos y probá de nuevo.');

            return null;
        }

        RateLimiter::hit($key, 600);

        try {
            $result = $online->reserve(
                $this->service,
                $this->professionalId === 'any' ? null : (int) $this->professionalId,
                Carbon::parse($this->date.' '.$this->time),
                [
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'phone' => $this->phone,
                    'email' => $this->email,
                    'notes' => $this->notes,
                ]
            );
        } catch (SlotUnavailableException $e) {
            $this->time = null;
            $this->step = 3;
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return null;
        } catch (Throwable $e) {
            report($e);
            $this->addError('confirm', 'No pudimos generar el pago. Probá de nuevo en unos minutos o escribinos por WhatsApp.');

            return null;
        }

        if ($result['link']?->init_point) {
            return redirect()->away($result['link']->init_point);
        }

        // Servicio sin seña (precio $0): queda reservado directamente
        $this->step = 6;

        return null;
    }

    #[Computed]
    public function service(): ?Service
    {
        return $this->serviceId ? Service::active()->find($this->serviceId) : null;
    }

    #[Computed]
    public function professionals()
    {
        return $this->service ? app(OnlineBookingService::class)->professionalsFor($this->service) : collect();
    }

    #[Computed]
    public function days(): array
    {
        if (! $this->service) {
            return [];
        }

        $online = app(OnlineBookingService::class);
        $last = $online->lastDate();
        $proId = $this->professionalId === 'any' ? null : (int) $this->professionalId;
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $day = today()->addDays($this->dayOffset + $i);

            if ($day->gt($last)) {
                break;
            }

            $days[] = [
                'date' => $day->toDateString(),
                'label' => ucfirst($day->translatedFormat('D')),
                'number' => $day->format('j'),
                'month' => $day->translatedFormat('M'),
                'available' => count($online->slotsFor($this->service, $day, $proId)) > 0,
            ];
        }

        return $days;
    }

    #[Computed]
    public function slots(): array
    {
        if (! $this->service || ! $this->date) {
            return [];
        }

        $proId = $this->professionalId === 'any' ? null : (int) $this->professionalId;

        return array_keys(app(OnlineBookingService::class)->slotsFor($this->service, Carbon::parse($this->date), $proId));
    }

    public function render(MercadoPagoService $mercadoPago)
    {
        $enabled = Settings::bool('online.enabled') && $mercadoPago->isConfigured();

        $categories = $enabled
            ? ServiceCategory::active()->ordered()
                ->with(['services' => fn ($q) => $q->active()->whereHas('professionals', fn ($p) => $p->online())])
                ->get()
                ->filter(fn ($c) => $c->services->isNotEmpty())
            : collect();

        return view('livewire.booking.public-booking', [
            'enabled' => $enabled,
            'categories' => $categories,
            'deposit' => $this->service ? Deposit::amountFor($this->service->price) : 0,
            'depositDescription' => Deposit::description(),
            'holdMinutes' => Settings::int('online.hold_minutes'),
            'terms' => Settings::get('online.terms'),
            'canGoBack' => $this->dayOffset > 0,
            'canGoForward' => today()->addDays($this->dayOffset + 7)->lte(app(OnlineBookingService::class)->lastDate()),
            'selectedProfessional' => $this->professionalId !== 'any' ? Professional::find((int) $this->professionalId) : null,
        ]);
    }
}

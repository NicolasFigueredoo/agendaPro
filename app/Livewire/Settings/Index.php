<?php

namespace App\Livewire\Settings;

use App\Services\MercadoPagoService;
use App\Support\Deposit;
use App\Support\Settings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Configuración: reservas online y Mercado Pago.
 * (Las reglas de agenda se suman acá en el módulo de reglas.)
 */
#[Layout('layouts.app')]
#[Title('Ajustes')]
class Index extends Component
{
    // Reservas online
    public bool $onlineEnabled = true;

    public $minHoursAhead = 2;

    public $maxDaysAhead = 30;

    public $holdMinutes = 30;

    public string $terms = '';

    // Mercado Pago
    public string $accessToken = '';

    public string $publicKey = '';

    public string $webhookSecret = '';

    public bool $sandbox = true;

    public string $depositType = 'percent';

    public $depositValue = 50;

    public $linkExpirationHours = 24;

    public function mount(): void
    {
        $this->onlineEnabled = Settings::bool('online.enabled');
        $this->minHoursAhead = Settings::int('online.min_hours_ahead');
        $this->maxDaysAhead = Settings::int('online.max_days_ahead');
        $this->holdMinutes = Settings::int('online.hold_minutes');
        $this->terms = (string) Settings::get('online.terms');

        $this->sandbox = Settings::bool('mp.sandbox');
        $this->depositType = (string) Settings::get('mp.deposit_type');
        $this->depositValue = Settings::int('mp.deposit_value');
        $this->linkExpirationHours = Settings::int('mp.link_expiration_hours');
    }

    public function saveOnline(): void
    {
        $this->validate([
            'onlineEnabled' => ['boolean'],
            'minHoursAhead' => ['required', 'integer', 'min:0', 'max:168'],
            'maxDaysAhead' => ['required', 'integer', 'min:1', 'max:365'],
            'holdMinutes' => ['required', 'integer', 'min:10', 'max:1440'],
            'terms' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'minHoursAhead' => 'anticipación mínima',
            'maxDaysAhead' => 'días hacia adelante',
            'holdMinutes' => 'tiempo para pagar',
            'terms' => 'condiciones',
        ]);

        Settings::setMany([
            'online.enabled' => $this->onlineEnabled,
            'online.min_hours_ahead' => (int) $this->minHoursAhead,
            'online.max_days_ahead' => (int) $this->maxDaysAhead,
            'online.hold_minutes' => (int) $this->holdMinutes,
            'online.terms' => trim($this->terms),
        ]);

        $this->dispatch('toast', message: 'Reservas online guardadas.');
    }

    public function saveMercadoPago(): void
    {
        $this->validate([
            'accessToken' => ['nullable', 'string', 'max:255'],
            'publicKey' => ['nullable', 'string', 'max:255'],
            'webhookSecret' => ['nullable', 'string', 'max:255'],
            'sandbox' => ['boolean'],
            'depositType' => ['required', 'in:percent,fixed'],
            'depositValue' => ['required', 'integer', 'min:1', $this->depositType === 'percent' ? 'max:100' : 'max:99999999'],
            'linkExpirationHours' => ['required', 'integer', 'min:1', 'max:720'],
        ], [], [
            'depositValue' => 'valor de la seña',
            'linkExpirationHours' => 'vencimiento del link',
        ]);

        // Las claves solo se pisan si se escribió algo nuevo
        foreach (['mp.access_token' => 'accessToken', 'mp.public_key' => 'publicKey', 'mp.webhook_secret' => 'webhookSecret'] as $key => $prop) {
            if (trim($this->{$prop}) !== '') {
                Settings::set($key, trim($this->{$prop}));
                $this->{$prop} = '';
            }
        }

        Settings::setMany([
            'mp.sandbox' => $this->sandbox,
            'mp.deposit_type' => $this->depositType,
            'mp.deposit_value' => (int) $this->depositValue,
            'mp.link_expiration_hours' => (int) $this->linkExpirationHours,
        ]);

        $this->dispatch('toast', message: 'Configuración de Mercado Pago guardada.');
    }

    public function clearSecret(string $key): void
    {
        if (in_array($key, Settings::ENCRYPTED, true)) {
            Settings::set($key, null);
            $this->dispatch('toast', message: 'Clave borrada.');
        }
    }

    public function testConnection(MercadoPagoService $mercadoPago): void
    {
        if (! $mercadoPago->isConfigured()) {
            $this->dispatch('toast', message: 'Primero cargá y guardá el Access Token.', type: 'error');

            return;
        }

        $mercadoPago->testCredentials()
            ? $this->dispatch('toast', message: '¡Conexión con Mercado Pago OK!')
            : $this->dispatch('toast', message: 'Mercado Pago rechazó las credenciales. Revisá el Access Token.', type: 'error');
    }

    protected function mask(?string $value): ?string
    {
        return $value ? '••••••'.substr($value, -4) : null;
    }

    public function render(MercadoPagoService $mercadoPago)
    {
        return view('livewire.settings.index', [
            'currentToken' => $this->mask(Settings::get('mp.access_token')),
            'currentPublicKey' => $this->mask(Settings::get('mp.public_key')),
            'currentSecret' => $this->mask(Settings::get('mp.webhook_secret')),
            'envFallback' => ! Settings::get('mp.access_token') && config('services.mercadopago.access_token'),
            'configured' => $mercadoPago->isConfigured(),
            'webhookUrl' => MercadoPagoService::publicUrl('webhooks/mercadopago'),
            'bookingUrl' => route('booking.public'),
            'depositExample' => Deposit::amountFor(20000),
        ]);
    }
}

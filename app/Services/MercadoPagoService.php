<?php

namespace App\Services;

use App\Models\Location;
use App\Models\MpPaymentLink;
use App\Support\Settings;
use Illuminate\Http\Request;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\PaymentMethod\PaymentMethodClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use RuntimeException;
use Throwable;

/**
 * Integración con Mercado Pago (Checkout Pro) usando el SDK oficial mercadopago/dx-php.
 *
 * En los tests se reemplaza por un fake: $this->instance(MercadoPagoService::class, $fake).
 */
class MercadoPagoService
{
    public function accessToken(): ?string
    {
        return Settings::get('mp.access_token') ?: config('services.mercadopago.access_token');
    }

    public function publicKey(): ?string
    {
        return Settings::get('mp.public_key') ?: config('services.mercadopago.public_key');
    }

    public function webhookSecret(): ?string
    {
        return Settings::get('mp.webhook_secret') ?: config('services.mercadopago.webhook_secret');
    }

    public function sandbox(): bool
    {
        return (bool) Settings::get('mp.sandbox', config('services.mercadopago.sandbox', true));
    }

    public function isConfigured(): bool
    {
        return (bool) $this->accessToken();
    }

    /** URL pública absoluta (para notification_url y back_urls). */
    public static function publicUrl(string $path): string
    {
        $base = config('services.mercadopago.public_base_url') ?: config('app.url');

        return rtrim($base, '/').'/'.ltrim($path, '/');
    }

    /**
     * Crea la preferencia de Checkout Pro para un link de pago.
     *
     * @return array{id: string, init_point: string, raw: array}
     */
    public function createPreference(MpPaymentLink $link): array
    {
        $this->configure();

        $booking = $link->booking()->with(['service', 'client'])->first();
        $location = Location::current();

        $title = sprintf(
            '%s - %s %s hs',
            $booking->service->name,
            $booking->start_at->format('d/m/Y'),
            $booking->start_at->format('H:i')
        );

        $successUrl = self::publicUrl("reserva/{$link->token}?resultado=exito");

        $request = [
            'items' => [[
                'id' => (string) $booking->service_id,
                'title' => ($link->type === MpPaymentLink::TYPE_SENA ? 'Seña: ' : '').$title,
                'description' => $title,
                'quantity' => 1,
                'currency_id' => 'ARS',
                'unit_price' => (float) $link->amount,
            ]],
            'payer' => array_filter([
                'name' => $booking->client->first_name,
                'surname' => $booking->client->last_name,
                'email' => $booking->client->email,
            ]),
            'external_reference' => (string) $link->id,
            'notification_url' => self::publicUrl('webhooks/mercadopago'),
            'back_urls' => [
                'success' => $successUrl,
                'pending' => self::publicUrl("reserva/{$link->token}?resultado=pendiente"),
                'failure' => self::publicUrl("reserva/{$link->token}?resultado=error"),
            ],
            'statement_descriptor' => mb_substr($location->name, 0, 22),
            'metadata' => ['booking_id' => $booking->id, 'link_id' => $link->id],
        ];

        // auto_return solo funciona con URLs https (en local sin túnel lo rechaza)
        if (str_starts_with($successUrl, 'https://')) {
            $request['auto_return'] = 'approved';
        }

        if ($link->expires_at) {
            $request['expires'] = true;
            $request['expiration_date_from'] = now()->format('Y-m-d\TH:i:s.vP');
            $request['expiration_date_to'] = $link->expires_at->format('Y-m-d\TH:i:s.vP');
        }

        try {
            $preference = (new PreferenceClient)->create($request);
        } catch (MPApiException $e) {
            throw new RuntimeException('Mercado Pago rechazó la preferencia: '.json_encode($e->getApiResponse()?->getContent()), 0, $e);
        }

        $initPoint = $this->sandbox() && ! empty($preference->sandbox_init_point)
            ? $preference->sandbox_init_point
            : $preference->init_point;

        return [
            'id' => (string) $preference->id,
            'init_point' => (string) $initPoint,
            'raw' => $this->content($preference),
        ];
    }

    /**
     * Consulta un pago a la API de Mercado Pago.
     *
     * @return array{id: string, status: string, status_detail: ?string, external_reference: ?string, amount: float, raw: array}
     */
    public function getPayment(string $paymentId): array
    {
        $this->configure();

        try {
            $payment = (new PaymentClient)->get((int) $paymentId);
        } catch (MPApiException $e) {
            throw new RuntimeException("No se pudo consultar el pago {$paymentId}: ".json_encode($e->getApiResponse()?->getContent()), 0, $e);
        }

        return [
            'id' => (string) $payment->id,
            'status' => (string) $payment->status,
            'status_detail' => $payment->status_detail ?? null,
            'external_reference' => $payment->external_reference ?? null,
            'amount' => (float) $payment->transaction_amount,
            'raw' => $this->content($payment),
        ];
    }

    /** Prueba las credenciales listando los medios de pago. */
    public function testCredentials(): bool
    {
        try {
            $this->configure();
            (new PaymentMethodClient)->list();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Valida el header x-signature de las notificaciones (webhooks).
     * Manifest: "id:{data.id};request-id:{x-request-id};ts:{ts};"
     */
    public function validSignature(Request $request): bool
    {
        $secret = $this->webhookSecret();
        $header = (string) $request->header('x-signature');

        if (! $secret || $header === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_map('trim', explode('=', $part, 2) + [1 => '']);
            $parts[$key] = $value;
        }

        if (empty($parts['ts']) || empty($parts['v1'])) {
            return false;
        }

        // PHP convierte "data.id" del query string en "data_id"
        $dataId = (string) ($request->query('data_id') ?? $request->input('data.id') ?? '');
        if ($dataId !== '' && ctype_alnum($dataId)) {
            $dataId = strtolower($dataId);
        }

        $requestId = (string) $request->header('x-request-id');

        $manifest = '';
        if ($dataId !== '') {
            $manifest .= "id:{$dataId};";
        }
        if ($requestId !== '') {
            $manifest .= "request-id:{$requestId};";
        }
        $manifest .= "ts:{$parts['ts']};";

        return hash_equals(hash_hmac('sha256', $manifest, $secret), $parts['v1']);
    }

    protected function configure(): void
    {
        $token = $this->accessToken();

        if (! $token) {
            throw new RuntimeException('Mercado Pago no está configurado (falta el Access Token).');
        }

        MercadoPagoConfig::setAccessToken($token);
    }

    protected function content(object $resource): array
    {
        try {
            return (array) $resource->getResponse()->getContent();
        } catch (Throwable) {
            return json_decode(json_encode($resource), true) ?: [];
        }
    }
}

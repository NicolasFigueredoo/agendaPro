<?php

namespace App\Http\Controllers;

use App\Services\BookingPaymentService;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * POST /webhooks/mercadopago
 *
 * - Si hay "Clave secreta" de webhooks configurada y la notificación trae x-signature, la valida.
 * - Nunca confía en el contenido de la notificación: consulta el pago a la API de MP.
 * - Idempotente: el mismo payment_id no registra dos pagos.
 * - Responde 500 ante errores para que Mercado Pago reintente.
 */
class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, MercadoPagoService $mercadoPago, BookingPaymentService $payments): JsonResponse
    {
        // Si viene firma, tiene que ser válida. Si no viene, igual es seguro: el pago se verifica contra la API de MP.
        if ($mercadoPago->webhookSecret() && $request->hasHeader('x-signature') && ! $mercadoPago->validSignature($request)) {
            Log::warning('Mercado Pago: firma inválida en webhook', ['query' => $request->query(), 'body' => $request->all()]);

            return response()->json(['error' => 'invalid signature'], 401);
        }

        $type = $request->input('type') ?? $request->query('type') ?? $request->query('topic');
        $paymentId = $request->input('data.id') ?? $request->query('data_id') ?? $request->query('id');

        if ($type !== 'payment' || ! $paymentId) {
            return response()->json(['ignored' => true]);
        }

        try {
            $payments->processMercadoPagoPayment((string) $paymentId);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => 'retry later'], 500);
        }

        return response()->json(['ok' => true]);
    }
}

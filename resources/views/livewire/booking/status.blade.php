<div @if ($state === 'waiting') wire:poll.4s @endif>
    <div class="card overflow-hidden">
        <div class="p-8 text-center">
            @if ($state === 'confirmed')
                <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><x-icon name="check" class="h-8 w-8" /></span>
                <h1 class="text-xl font-semibold">¡Tu turno está confirmado!</h1>
                <p class="mt-2 text-sm text-gray-500">
                    Recibimos tu seña de @ars($link->amount).
                    @if ($booking->client?->email) Te mandamos la confirmación a {{ $booking->client->email }}. @endif
                </p>
            @elseif ($state === 'waiting')
                <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                    <svg class="h-8 w-8 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                </span>
                <h1 class="text-xl font-semibold">Estamos esperando la confirmación del pago</h1>
                <p class="mt-2 text-sm text-gray-500">
                    @if ($resultado === 'pendiente')
                        Mercado Pago está procesando tu pago. Apenas se acredite, tu turno queda confirmado.
                    @else
                        Si todavía no pagaste, tenés tiempo hasta las {{ $booking->hold_expires_at->format('H:i') }} hs.
                    @endif
                </p>
            @elseif ($state === 'failed')
                <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600"><x-icon name="x" class="h-8 w-8" /></span>
                <h1 class="text-xl font-semibold">El pago no se completó</h1>
                <p class="mt-2 text-sm text-gray-500">Tu horario sigue guardado hasta las {{ $booking->hold_expires_at->format('H:i') }} hs. Podés intentar de nuevo.</p>
            @else
                <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-500"><x-icon name="calendar" class="h-8 w-8" /></span>
                <h1 class="text-xl font-semibold">La reserva venció</h1>
                <p class="mt-2 text-sm text-gray-500">No recibimos la seña a tiempo y el horario se liberó. Podés volver a reservar.</p>
            @endif
        </div>

        <dl class="space-y-2 border-t border-gray-100 bg-gray-50 p-6 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Servicio</dt><dd class="text-right font-medium">{{ $booking->service->name }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Día</dt><dd class="text-right font-medium">{{ ucfirst($booking->start_at->translatedFormat('l j \d\e F')) }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Hora</dt><dd class="text-right font-medium">{{ $booking->start_at->format('H:i') }} hs</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Con</dt><dd class="text-right font-medium">{{ $booking->professional->name }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Seña</dt><dd class="text-right font-medium">@ars($link->amount)</dd></div>
            @if ($state === 'confirmed')
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Resta abonar en el local</dt><dd class="text-right font-medium">@ars(max(0, $booking->price - $booking->paidAmount()))</dd></div>
            @endif
        </dl>

        <div class="space-y-2 p-6">
            @if (in_array($state, ['waiting', 'failed'], true) && $link->isPayable())
                <a href="{{ $link->init_point }}" class="btn w-full bg-[#009ee3] py-3 text-white hover:bg-[#0089c7]">
                    {{ $state === 'failed' ? 'Intentar el pago de nuevo' : 'Ir a pagar la seña' }}
                </a>
            @endif

            @if ($state === 'expired')
                <a href="{{ route('booking.public') }}" class="btn btn-primary w-full">Reservar de nuevo</a>
            @endif

            @if ($wa = \App\Support\Phone::whatsappUrl(\App\Models\Location::current()->phone))
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-secondary w-full">¿Dudas? Escribinos por WhatsApp</a>
            @endif
        </div>
    </div>
</div>

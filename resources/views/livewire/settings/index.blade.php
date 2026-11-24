<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold">Ajustes</h1>
        <p class="text-sm text-gray-500">Reservas online y Mercado Pago.</p>
    </div>

    <div class="max-w-3xl space-y-6">
        {{-- Link de reservas --}}
        <div class="card flex flex-col gap-3 p-5 sm:flex-row sm:items-center">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold">Link para que los clientes reserven</p>
                <p class="truncate text-sm text-brand-700">{{ $bookingUrl }}</p>
                <p class="text-xs text-gray-500">Compartilo en Instagram, WhatsApp o Google.</p>
            </div>
            <div class="flex gap-2" x-data="{ copied: false }">
                <button type="button" class="btn btn-secondary" x-on:click="navigator.clipboard.writeText('{{ $bookingUrl }}'); copied = true; setTimeout(() => copied = false, 2000)">
                    <span x-text="copied ? '¡Copiado!' : 'Copiar'"></span>
                </button>
                <a href="{{ $bookingUrl }}" target="_blank" class="btn btn-secondary">Abrir</a>
            </div>
        </div>

        @unless ($configured)
            <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
                <strong>Las reservas online están apagadas</strong> hasta que cargues el Access Token de Mercado Pago (la seña es obligatoria).
            </div>
        @endunless

        {{-- Reservas online --}}
        <form wire:submit="saveOnline" class="card space-y-4 p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold">Reservas online</h2>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" wire:model="onlineEnabled" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm">Habilitadas</span>
                </label>
            </div>

            <p class="text-sm text-gray-500">Solo aparecen las profesionales con "Acepta reservas online" activado (en Profesionales) y los servicios que hacen.</p>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="label">Anticipación mínima (horas)</label>
                    <input type="number" min="0" wire:model="minHoursAhead" class="input">
                    @error('minHoursAhead') <p class="input-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Hasta cuántos días adelante</label>
                    <input type="number" min="1" wire:model="maxDaysAhead" class="input">
                    @error('maxDaysAhead') <p class="input-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Minutos para pagar la seña</label>
                    <input type="number" min="10" wire:model="holdMinutes" class="input">
                    @error('holdMinutes') <p class="input-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-gray-500">Mientras el cliente paga, el horario queda retenido. Si no paga a tiempo, se libera solo.</p>

            <div>
                <label class="label">Condiciones de la seña (las ve el cliente antes de pagar)</label>
                <textarea rows="3" wire:model="terms" class="input"></textarea>
                @error('terms') <p class="input-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>

        {{-- Mercado Pago --}}
        <form wire:submit="saveMercadoPago" class="card space-y-4 p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold">Mercado Pago</h2>
                <span @class(['badge', 'bg-emerald-100 text-emerald-700' => $configured, 'bg-gray-200 text-gray-600' => ! $configured])>
                    {{ $configured ? 'Conectado' : 'Sin configurar' }}
                </span>
            </div>

            <p class="text-sm text-gray-500">
                Las credenciales están en <a href="https://www.mercadopago.com.ar/developers/panel/app" target="_blank" rel="noopener" class="text-brand-700 underline">Mercado Pago Developers → Tus integraciones</a> → tu aplicación → Credenciales. Se guardan encriptadas.
            </p>

            @if ($envFallback)
                <p class="rounded-lg bg-sky-50 px-3 py-2 text-xs text-sky-800">Usando el Access Token del archivo .env.</p>
            @endif

            @foreach ([
                ['accessToken', 'Access Token', 'mp.access_token', $currentToken, 'APP_USR-... o TEST-...'],
                ['publicKey', 'Public Key', 'mp.public_key', $currentPublicKey, 'APP_USR-... o TEST-...'],
                ['webhookSecret', 'Clave secreta de webhooks', 'mp.webhook_secret', $currentSecret, 'Webhooks → Clave secreta'],
            ] as [$prop, $label, $key, $current, $placeholder])
                <div>
                    <label class="label">{{ $label }}</label>
                    <input type="password" wire:model="{{ $prop }}" class="input font-mono" autocomplete="off" placeholder="{{ $current ? 'Guardada '.$current.' (escribí para reemplazar)' : $placeholder }}">
                    @if ($current)
                        <button type="button" wire:click="clearSecret('{{ $key }}')" wire:confirm="¿Borrar {{ $label }}?" class="mt-1 text-xs text-red-600 hover:underline">Borrar</button>
                    @endif
                    @error($prop) <p class="input-error">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div>
                <label class="label">URL de notificaciones (webhook)</label>
                <div class="flex gap-2" x-data="{ copied: false }">
                    <input type="text" readonly value="{{ $webhookUrl }}" class="input bg-gray-50 font-mono text-xs">
                    <button type="button" class="btn btn-secondary" x-on:click="navigator.clipboard.writeText('{{ $webhookUrl }}'); copied = true; setTimeout(() => copied = false, 2000)">
                        <span x-text="copied ? '¡Copiada!' : 'Copiar'"></span>
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">Cargala en tu aplicación de MP → Webhooks, con el evento "Pagos". Tiene que ser https y pública.</p>
            </div>

            <label class="inline-flex items-center gap-2">
                <input type="checkbox" wire:model="sandbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span class="text-sm">Modo prueba (sandbox)</span>
            </label>

            <div class="grid gap-4 border-t border-gray-100 pt-4 sm:grid-cols-3">
                <div>
                    <label class="label">Tipo de seña</label>
                    <select wire:model.live="depositType" class="input">
                        <option value="percent">Porcentaje</option>
                        <option value="fixed">Monto fijo</option>
                    </select>
                </div>
                <div>
                    <label class="label">{{ $depositType === 'percent' ? 'Porcentaje' : 'Monto ($)' }}</label>
                    <input type="number" min="1" wire:model="depositValue" class="input">
                    @error('depositValue') <p class="input-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Vencimiento de links manuales (h)</label>
                    <input type="number" min="1" wire:model="linkExpirationHours" class="input">
                    @error('linkExpirationHours') <p class="input-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-gray-500">Con la configuración guardada, un servicio de $ 20.000 lleva una seña de @ars($depositExample).</p>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="testConnection" class="btn btn-secondary" wire:loading.attr="disabled" wire:target="testConnection">Probar conexión</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

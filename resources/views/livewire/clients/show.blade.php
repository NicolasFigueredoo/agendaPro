<div>
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('clients.index') }}" wire:navigate class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Volver">
                <x-icon name="chevron-left" class="h-5 w-5" />
            </a>
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-100 text-lg font-semibold text-brand-700">{{ $client->initials() }}</span>
            <div class="min-w-0">
                <h1 class="truncate text-xl font-semibold">{{ $client->fullName() }}</h1>
                <p class="text-sm text-gray-500">
                    Cliente N° {{ $client->client_number }} · desde {{ $client->created_at->translatedFormat('F Y') }}
                    @if ($stats['total'] === 0)
                        <span class="badge ml-1 bg-brand-100 text-brand-700">Cliente nuevo</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($wa = \App\Support\Phone::whatsappUrl($client->phone))
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-secondary">WhatsApp</a>
            @endif
            <button type="button" wire:click="edit" class="btn btn-secondary">
                <x-icon name="pencil" class="h-4 w-4" /> Editar
            </button>
            <button type="button" wire:click="delete" wire:confirm="¿Eliminar a {{ $client->fullName() }}? Su historial de turnos y pagos se conserva." class="btn btn-secondary text-red-600">
                <x-icon name="trash" class="h-4 w-4" />
            </button>
        </div>
    </div>

    {{-- Indicadores --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total gastado</p>
            <p class="mt-1 text-xl font-semibold">@ars($stats['spent'])</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Turnos</p>
            <p class="mt-1 text-xl font-semibold">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500">{{ $stats['attended'] }} asistidos</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Inasistencias</p>
            <p @class(['mt-1 text-xl font-semibold', 'text-red-600' => $stats['no_shows'] > 0])>{{ $stats['no_shows'] }}</p>
            <p class="text-xs text-gray-500">{{ $stats['cancelled'] }} cancelados</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Última visita</p>
            <p class="mt-1 text-xl font-semibold">{{ $stats['last_visit']?->format('d/m/Y') ?? '—' }}</p>
            @if ($stats['next'])
                <p class="text-xs text-gray-500">Próximo: {{ $stats['next']->start_at->format('d/m H:i') }}</p>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Datos --}}
        <div class="card h-fit p-5">
            <h2 class="mb-4 font-semibold">Datos</h2>
            <dl class="space-y-3 text-sm">
                @foreach ([
                    'Teléfono' => $client->phone,
                    'Email' => $client->email,
                    'DNI' => $client->dni,
                    'Nacimiento' => $client->birth_date ? $client->birth_date->format('d/m/Y').' ('.$client->age().' años)' : null,
                    'Género' => $client->gender?->label(),
                    'Dirección' => $client->address,
                    'Provincia' => $client->province,
                ] as $label => $value)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">{{ $label }}</dt>
                        <dd class="text-right font-medium break-all">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($client->notes)
                <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide">Notas</p>
                    {!! nl2br(e($client->notes)) !!}
                </div>
            @endif
        </div>

        {{-- Historial --}}
        <div class="card overflow-hidden lg:col-span-2">
            <h2 class="border-b border-gray-200 px-5 py-4 font-semibold">Historial de turnos</h2>

            <ul class="divide-y divide-gray-100">
                @forelse ($bookings as $booking)
                    @php $paid = $booking->payments->sum('amount'); @endphp
                    <li wire:key="bk-{{ $booking->id }}" class="px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-medium">{{ $booking->service?->name ?? 'Servicio eliminado' }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ ucfirst($booking->start_at->translatedFormat('D d/m/Y')) }} · {{ $booking->start_at->format('H:i') }} a {{ $booking->end_at->format('H:i') }}
                                    · <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full" style="background-color: {{ $booking->professional?->color }}"></span>{{ $booking->professional?->name }}</span>
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="badge {{ $booking->status->badgeClasses() }}">{{ $booking->status->label() }}</span>
                                <span class="badge {{ $booking->payment_status->badgeClasses() }}">{{ $booking->payment_status->label() }}</span>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                            <span>Precio: <strong>@ars($booking->price)</strong></span>
                            @if ($paid > 0)
                                <span>Pagado: <strong>@ars($paid)</strong></span>
                            @endif
                            @if ($booking->status !== \App\Enums\BookingStatus::Cancelado && $paid < $booking->price)
                                <span class="text-amber-700">Saldo: <strong>@ars($booking->price - $paid)</strong></span>
                            @endif
                        </div>

                        @if ($booking->payments->isNotEmpty())
                            <ul class="mt-2 space-y-0.5 text-xs text-gray-500">
                                @foreach ($booking->payments as $payment)
                                    <li>{{ $payment->paid_at->format('d/m/Y H:i') }} · {{ $payment->method->label() }} · @ars($payment->amount){{ $payment->notes ? ' · '.$payment->notes : '' }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($booking->notes)
                            <p class="mt-2 text-xs italic text-gray-500">{{ $booking->notes }}</p>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-gray-500">Todavía no tiene turnos.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Modal edición --}}
    <div x-data="{ open: $wire.entangle('showModal') }" x-show="open" x-cloak x-on:keydown.escape.window="open = false" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div x-show="open" x-transition.opacity x-on:click="open = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="open" x-transition class="relative max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-6 shadow-xl sm:max-w-2xl sm:rounded-2xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Editar cliente</h2>
                <button type="button" x-on:click="open = false" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100" aria-label="Cerrar">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <form wire:submit="save" class="space-y-4">
                @include('livewire.clients.partials.form-fields')

                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" x-on:click="open = false" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

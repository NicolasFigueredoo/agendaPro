@php
    $upcoming = \App\Models\Booking::query()
        ->with(['client', 'service', 'professional', 'payments'])
        ->where('start_at', '>=', today())
        ->where('start_at', '<', today()->addDays(8))
        ->active()
        ->orderBy('start_at')
        ->get()
        ->groupBy(fn ($b) => $b->start_at->toDateString());
@endphp

<x-app-layout title="Dashboard">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold">Hola, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-gray-500">{{ ucfirst(now()->translatedFormat('l j \d\e F \d\e Y')) }}</p>
        </div>
        <a href="{{ route('booking.public') }}" target="_blank" class="btn btn-secondary">Ver página de reservas</a>
    </div>

    <div class="card overflow-hidden">
        <h2 class="border-b border-gray-200 px-5 py-4 font-semibold">Próximos turnos (7 días)</h2>

        @forelse ($upcoming as $day => $bookings)
            <div>
                <p class="bg-gray-50 px-5 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ ucfirst(\Carbon\Carbon::parse($day)->translatedFormat('l j \d\e F')) }}
                    @if (\Carbon\Carbon::parse($day)->isToday()) · Hoy @endif
                </p>
                <ul class="divide-y divide-gray-100">
                    @foreach ($bookings as $booking)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3" wire:key="up-{{ $booking->id }}">
                            <span class="w-24 shrink-0 text-sm font-semibold">{{ $booking->start_at->format('H:i') }}–{{ $booking->end_at->format('H:i') }}</span>
                            <span class="flex min-w-0 flex-1 items-center gap-2">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $booking->professional?->color }}"></span>
                                <span class="min-w-0">
                                    <a href="{{ route('clients.show', $booking->client_id) }}" wire:navigate class="block truncate font-medium hover:underline">{{ $booking->client?->fullName() }}</a>
                                    <span class="block truncate text-xs text-gray-500">{{ $booking->service?->name }} · {{ $booking->professional?->name }}</span>
                                </span>
                            </span>
                            <span class="flex flex-wrap gap-1">
                                @if ($booking->isOnline())
                                    <span class="badge bg-indigo-100 text-indigo-700">Online</span>
                                @endif
                                @if ($booking->isAwaitingDeposit())
                                    <span class="badge bg-amber-100 text-amber-800">Esperando seña hasta {{ $booking->hold_expires_at->format('H:i') }}</span>
                                @else
                                    <span class="badge {{ $booking->status->badgeClasses() }}">{{ $booking->status->label() }}</span>
                                    <span class="badge {{ $booking->payment_status->badgeClasses() }}">{{ $booking->payment_status->label() }}</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="px-5 py-10 text-center text-sm text-gray-500">No hay turnos en los próximos 7 días.</p>
        @endforelse
    </div>
</x-app-layout>

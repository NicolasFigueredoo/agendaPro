<div x-data="{ filtersOpen: {{ $activeFilters ? 'true' : 'false' }} }">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold">Clientes</h1>
            <p class="text-sm text-gray-500">{{ number_format($clients->total(), 0, ',', '.') }} {{ $clients->total() === 1 ? 'cliente' : 'clientes' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('clients.import') }}" wire:navigate class="btn btn-secondary">Importar</a>
            <button type="button" wire:click="export" class="btn btn-secondary" wire:loading.attr="disabled" wire:target="export">Exportar Excel</button>
            <button type="button" wire:click="create" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" /> Nuevo cliente
            </button>
        </div>
    </div>

    {{-- Buscador y filtros --}}
    <div class="card mb-4">
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nombre, apellido, DNI, email o teléfono" class="input pl-9">
            </div>
            <button type="button" x-on:click="filtersOpen = !filtersOpen" class="btn btn-secondary">
                <x-icon name="adjustments" class="h-4 w-4" />
                Filtros
                @if ($activeFilters)
                    <span class="badge bg-brand-600 text-white">{{ $activeFilters }}</span>
                @endif
            </button>
        </div>

        <div x-show="filtersOpen" x-collapse x-cloak class="border-t border-gray-200 p-4">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="label">Profesional</label>
                    <select wire:model.live="professional" class="input">
                        <option value="">Todos</option>
                        @foreach ($professionals as $pro)
                            <option value="{{ $pro->id }}">{{ $pro->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Servicio</label>
                    <select wire:model.live="service" class="input">
                        <option value="">Todos</option>
                        @foreach ($services as $srv)
                            <option value="{{ $srv->id }}">{{ $srv->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Estado de reserva</label>
                    <select wire:model.live="status" class="input">
                        <option value="">Todos</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">¿Reservó alguna vez?</label>
                    <select wire:model.live="booked" class="input">
                        <option value="">Indistinto</option>
                        <option value="yes">Sí</option>
                        <option value="no">No</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="label">Cumpleaños entre</label>
                    <div class="flex items-center gap-2">
                        <input type="date" wire:model.live="birthday_from" class="input">
                        <span class="text-sm text-gray-400">y</span>
                        <input type="date" wire:model.live="birthday_to" class="input">
                    </div>
                    <div class="mt-1.5 flex flex-wrap gap-2 text-xs">
                        <button type="button" wire:click="birthdayPreset('today')" class="font-medium text-brand-700 hover:underline">Hoy</button>
                        <button type="button" wire:click="birthdayPreset('week')" class="font-medium text-brand-700 hover:underline">Próximos 7 días</button>
                        <button type="button" wire:click="birthdayPreset('month')" class="font-medium text-brand-700 hover:underline">Este mes</button>
                        <span class="text-gray-400">· No importa el año</span>
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label class="label">Alta entre</label>
                    <div class="flex items-center gap-2">
                        <input type="date" wire:model.live="created_from" class="input">
                        <span class="text-sm text-gray-400">y</span>
                        <input type="date" wire:model.live="created_to" class="input">
                    </div>
                </div>
            </div>

            @if ($activeFilters)
                <button type="button" wire:click="clearFilters" class="mt-4 text-sm font-medium text-gray-500 hover:text-gray-800">Limpiar filtros</button>
            @endif
        </div>
    </div>

    {{-- Escritorio --}}
    <div class="card hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-sort-th column="client_number" :$sort :$direction>N°</x-sort-th>
                    <x-sort-th column="last_name" :$sort :$direction>Cliente</x-sort-th>
                    <th class="th">Teléfono</th>
                    <th class="th">Email</th>
                    <th class="th">DNI</th>
                    <x-sort-th column="bookings_count" :$sort :$direction class="text-right">Turnos</x-sort-th>
                    <x-sort-th column="last_visit" :$sort :$direction>Última visita</x-sort-th>
                    <x-sort-th column="created_at" :$sort :$direction>Alta</x-sort-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" wire:loading.class="opacity-60">
                @forelse ($clients as $client)
                    <tr wire:key="cl-{{ $client->id }}" class="cursor-pointer hover:bg-gray-50" onclick="Livewire.navigate('{{ route('clients.show', $client) }}')">
                        <td class="td text-gray-500">{{ $client->client_number }}</td>
                        <td class="td">
                            <span class="font-medium text-gray-900">{{ $client->last_name }}, {{ $client->first_name }}</span>
                            @if ($client->birth_date && $client->birth_date->isBirthday())
                                <span title="¡Cumple hoy!">🎂</span>
                            @endif
                        </td>
                        <td class="td">{{ $client->phone ?: '—' }}</td>
                        <td class="td max-w-[14rem] truncate">{{ $client->email ?: '—' }}</td>
                        <td class="td">{{ $client->dni ?: '—' }}</td>
                        <td class="td text-right">{{ $client->bookings_count }}</td>
                        <td class="td">{{ $client->last_visit ? \Carbon\Carbon::parse($client->last_visit)->format('d/m/Y') : '—' }}</td>
                        <td class="td text-gray-500">{{ $client->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">No hay clientes que coincidan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Celular --}}
    <ul class="card divide-y divide-gray-100 md:hidden">
        @forelse ($clients as $client)
            <li wire:key="clm-{{ $client->id }}">
                <a href="{{ route('clients.show', $client) }}" wire:navigate class="flex items-center gap-3 p-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700">{{ $client->initials() }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-medium">{{ $client->first_name }} {{ $client->last_name }}</span>
                        <span class="block truncate text-xs text-gray-500">{{ $client->phone ?: ($client->email ?: 'Sin contacto') }}</span>
                    </span>
                    <span class="text-right text-xs text-gray-500">
                        {{ $client->bookings_count }} {{ $client->bookings_count === 1 ? 'turno' : 'turnos' }}
                    </span>
                </a>
            </li>
        @empty
            <li class="p-6 text-center text-sm text-gray-500">No hay clientes que coincidan.</li>
        @endforelse
    </ul>

    @if ($clients->hasPages())
        <div class="mt-4">{{ $clients->links() }}</div>
    @endif

    {{-- Modal nuevo cliente --}}
    <div x-data="{ open: $wire.entangle('showModal') }" x-show="open" x-cloak x-on:keydown.escape.window="open = false" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div x-show="open" x-transition.opacity x-on:click="open = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="open" x-transition class="relative max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-6 shadow-xl sm:max-w-2xl sm:rounded-2xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Nuevo cliente</h2>
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

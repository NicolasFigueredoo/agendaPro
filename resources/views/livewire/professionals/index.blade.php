<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold">Profesionales</h1>
            <p class="text-sm text-gray-500">Servicios que realiza cada una y su horario semanal.</p>
        </div>
        <a href="{{ route('professionals.create') }}" wire:navigate class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Nuevo profesional
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($professionals as $pro)
            @php
                $days = $pro->schedules->where('active', true)->pluck('weekday');
            @endphp
            <div wire:key="pro-{{ $pro->id }}" @class(['card flex flex-col p-5', 'opacity-60' => ! $pro->active])>
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-lg font-semibold text-white" style="background-color: {{ $pro->color }}">
                        {{ mb_strtoupper(mb_substr($pro->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $pro->name }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $pro->user?->email ?? 'Sin usuario vinculado' }}</p>
                    </div>
                    <span @class(['badge', 'bg-emerald-100 text-emerald-700' => $pro->active, 'bg-gray-200 text-gray-600' => ! $pro->active])>
                        {{ $pro->active ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>

                <dl class="mt-4 space-y-1 text-sm">
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500">Servicios</dt>
                        <dd class="font-medium">{{ $pro->services_count }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500">Días</dt>
                        <dd class="font-medium">{{ \App\Support\Weekdays::summary($days) }}</dd>
                    </div>
                    @if (! is_null($pro->commission_percent))
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">Comisión</dt>
                            <dd class="font-medium">{{ $pro->commission_percent }}%</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-5 flex gap-2 border-t border-gray-100 pt-4">
                    <a href="{{ route('professionals.edit', $pro) }}" wire:navigate class="btn btn-secondary btn-sm flex-1">
                        <x-icon name="pencil" class="h-4 w-4" /> Editar
                    </a>
                    <button type="button" wire:click="toggleActive({{ $pro->id }})" class="btn btn-secondary btn-sm">
                        {{ $pro->active ? 'Desactivar' : 'Activar' }}
                    </button>
                    <button type="button" wire:click="delete({{ $pro->id }})" wire:confirm="¿Eliminar a {{ $pro->name }}? Si tiene turnos, conviene desactivarla." class="btn btn-secondary btn-sm text-red-600" title="Eliminar">
                        <x-icon name="trash" class="h-4 w-4" />
                    </button>
                </div>
            </div>
        @empty
            <div class="card p-8 text-center text-sm text-gray-500 sm:col-span-2 xl:col-span-3">
                Todavía no cargaste profesionales.
            </div>
        @endforelse
    </div>
</div>

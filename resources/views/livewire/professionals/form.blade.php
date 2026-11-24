<div>
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('professionals.index') }}" wire:navigate class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Volver">
            <x-icon name="chevron-left" class="h-5 w-5" />
        </a>
        <div>
            <h1 class="text-xl font-semibold">{{ $professionalId ? 'Editar profesional' : 'Nuevo profesional' }}</h1>
            <p class="text-sm text-gray-500">{{ $professionalId ? $name : 'Datos, servicios y horario semanal.' }}</p>
        </div>
    </div>

    <form wire:submit="save" class="max-w-4xl space-y-6 pb-24">
        {{-- Datos --}}
        <div class="card p-5 sm:p-6">
            <h2 class="mb-4 font-semibold">Datos</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="p-name">Nombre *</label>
                    <input id="p-name" type="text" wire:model="name" class="input">
                    @error('name') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="p-user">Usuario asociado</label>
                    <select id="p-user" wire:model="userId" class="input">
                        <option value="">Sin usuario (no entra al sistema)</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} · {{ $u->email }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Solo usuarios con rol Profesional.
                        <a href="{{ route('admin.users') }}" wire:navigate class="text-brand-700 underline">Crear usuario</a>
                    </p>
                    @error('userId') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <span class="label">Color en la agenda *</span>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($colors as $c)
                            <button type="button" wire:click="$set('color', '{{ $c }}')"
                                @class(['h-8 w-8 rounded-full ring-offset-2 transition', 'ring-2 ring-gray-900' => strtolower($color) === $c])
                                style="background-color: {{ $c }}" aria-label="Color {{ $c }}"></button>
                        @endforeach
                        <input type="color" wire:model.live="color" class="h-8 w-10 cursor-pointer rounded border-0 bg-transparent p-0" title="Otro color">
                    </div>
                    @error('color') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="p-commission">Comisión</label>
                        <div class="relative">
                            <input id="p-commission" type="number" min="0" max="100" step="1" inputmode="numeric" wire:model="commission" class="input pr-8" placeholder="Opcional">
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">%</span>
                        </div>
                        @error('commission') <p class="input-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col justify-end gap-2 pb-2">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-gray-700">Activo</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="acceptsOnline" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-gray-700">Acepta reservas online</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Servicios --}}
        <div class="card p-5 sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="font-semibold">Servicios que realiza <span class="text-sm font-normal text-gray-500">({{ count($serviceIds) }})</span></h2>
                <button type="button" wire:click="toggleAllServices" class="text-xs font-medium text-brand-700 hover:underline">Seleccionar todos</button>
            </div>

            <div class="space-y-5">
                @forelse ($categories as $category)
                    @continue($category->services->isEmpty())
                    @php
                        $catIds = $category->services->pluck('id')->map(fn ($id) => (string) $id);
                        $allChecked = $catIds->diff($serviceIds)->isEmpty();
                    @endphp
                    <div wire:key="pcat-{{ $category->id }}">
                        <label class="mb-2 flex items-center gap-2">
                            <input type="checkbox" wire:click="toggleCategory({{ $category->id }})" @checked($allChecked) class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-sm font-semibold">{{ $category->name }}</span>
                        </label>
                        <div class="grid gap-x-4 gap-y-1.5 pl-6 sm:grid-cols-2">
                            @foreach ($category->services as $service)
                                <label wire:key="ps-{{ $service->id }}" @class(['flex items-center gap-2', 'opacity-60' => ! $service->active])>
                                    <input type="checkbox" wire:model.live="serviceIds" value="{{ $service->id }}" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                    <span class="text-sm">{{ $service->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Todavía no hay servicios cargados.</p>
                @endforelse
            </div>
        </div>

        {{-- Horario --}}
        <div class="card p-5 sm:p-6">
            <h2 class="mb-1 font-semibold">Horario semanal</h2>
            <p class="mb-4 text-sm text-gray-500">Fuera de este horario y en los descansos, la agenda muestra "Profesional no disponible".</p>

            <div class="divide-y divide-gray-100">
                @foreach ($weekdays as $day => $dayName)
                    <div wire:key="day-{{ $day }}" class="py-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="flex w-32 items-center gap-2">
                                <input type="checkbox" wire:model.live="schedule.{{ $day }}.active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span class="text-sm font-medium">{{ $dayName }}</span>
                            </label>

                            @if ($schedule[$day]['active'])
                                <div class="flex items-center gap-2">
                                    <input type="time" step="900" wire:model="schedule.{{ $day }}.start" class="input w-28">
                                    <span class="text-sm text-gray-400">a</span>
                                    <input type="time" step="900" wire:model="schedule.{{ $day }}.end" class="input w-28">
                                </div>
                                <div class="flex gap-3">
                                    <button type="button" wire:click="addBreak({{ $day }})" class="text-xs font-medium text-gray-600 hover:underline">+ Descanso</button>
                                    <button type="button" wire:click="copyToAll({{ $day }})" class="text-xs font-medium text-brand-700 hover:underline">Copiar en todos</button>
                                </div>
                            @else
                                <span class="text-sm text-gray-400">No trabaja</span>
                            @endif
                        </div>

                        @error("schedule.$day.start") <p class="input-error">{{ $message }}</p> @enderror
                        @error("schedule.$day.end") <p class="input-error">{{ $message }}</p> @enderror

                        @if ($schedule[$day]['active'] && count($schedule[$day]['breaks']))
                            <div class="mt-3 space-y-2 sm:pl-32">
                                @foreach ($schedule[$day]['breaks'] as $i => $break)
                                    <div wire:key="break-{{ $day }}-{{ $i }}">
                                        <div class="flex items-center gap-2">
                                            <span class="w-16 text-xs text-gray-500">Descanso</span>
                                            <input type="time" step="900" wire:model="schedule.{{ $day }}.breaks.{{ $i }}.start" class="input w-28">
                                            <span class="text-sm text-gray-400">a</span>
                                            <input type="time" step="900" wire:model="schedule.{{ $day }}.breaks.{{ $i }}.end" class="input w-28">
                                            <button type="button" wire:click="removeBreak({{ $day }}, {{ $i }})" class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600" aria-label="Quitar descanso">
                                                <x-icon name="x" class="h-4 w-4" />
                                            </button>
                                        </div>
                                        @error("schedule.$day.breaks.$i.start") <p class="input-error">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Barra fija --}}
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-gray-200 bg-white/95 px-4 py-3 backdrop-blur lg:pl-72">
            <div class="flex justify-end gap-2">
                <a href="{{ route('professionals.index') }}" wire:navigate class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Guardar</button>
            </div>
        </div>
    </form>
</div>

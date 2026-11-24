<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold">Servicios</h1>
            <p class="text-sm text-gray-500">Precio, duración y quién realiza cada servicio.</p>
        </div>
        <button type="button" wire:click="create" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Nuevo servicio
        </button>
    </div>

    <x-services-tabs />

    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <div class="relative flex-1 sm:max-w-sm">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar servicio" class="input pl-9">
        </div>
        <select wire:model.live="status" class="input sm:w-44">
            <option value="all">Todos</option>
            <option value="active">Solo activos</option>
            <option value="inactive">Solo inactivos</option>
        </select>
    </div>

    <div class="space-y-6">
        @forelse ($categories as $category)
            <section wire:key="cat-{{ $category->id }}" class="card overflow-hidden">
                <header class="flex items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <h2 class="font-semibold">
                        {{ $category->name }}
                        <span class="ml-1 text-xs font-normal text-gray-500">({{ $category->services->count() }})</span>
                        @unless ($category->active) <span class="badge ml-1 bg-gray-200 text-gray-600">Inactiva</span> @endunless
                    </h2>
                    <button type="button" wire:click="create({{ $category->id }})" class="text-xs font-medium text-brand-700 hover:underline">+ Agregar</button>
                </header>

                <ul class="divide-y divide-gray-100">
                    @forelse ($category->services as $service)
                        <li wire:key="srv-{{ $service->id }}" @class(['flex items-center gap-3 px-4 py-3', 'opacity-60' => ! $service->active])>
                            @if (! $search && $status === 'all')
                                <div class="hidden flex-col sm:flex">
                                    <button type="button" wire:click="move({{ $service->id }}, 'up')" @disabled($loop->first) class="rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-30" title="Subir">
                                        <x-icon name="chevron-left" class="h-4 w-4 rotate-90" />
                                    </button>
                                    <button type="button" wire:click="move({{ $service->id }}, 'down')" @disabled($loop->last) class="rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-30" title="Bajar">
                                        <x-icon name="chevron-right" class="h-4 w-4 rotate-90" />
                                    </button>
                                </div>
                            @endif

                            <button type="button" wire:click="edit({{ $service->id }})" class="min-w-0 flex-1 text-left">
                                <p class="truncate font-medium">{{ $service->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $service->durationLabel() }}
                                    · {{ $service->professionals_count }} {{ $service->professionals_count === 1 ? 'profesional' : 'profesionales' }}
                                    @unless ($service->active) · <span class="font-medium">Inactivo</span> @endunless
                                </p>
                            </button>

                            <span class="whitespace-nowrap text-sm font-semibold">@ars($service->price)</span>

                            <div class="hidden gap-1 sm:flex">
                                <button type="button" wire:click="toggleActive({{ $service->id }})" class="rounded-lg px-2 py-1 text-xs font-medium text-gray-500 hover:bg-gray-100">
                                    {{ $service->active ? 'Desactivar' : 'Activar' }}
                                </button>
                                <button type="button" wire:click="delete({{ $service->id }})" wire:confirm="¿Eliminar {{ $service->name }}?" class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600" title="Eliminar">
                                    <x-icon name="trash" class="h-4 w-4" />
                                </button>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-4 text-sm text-gray-500">Sin servicios en esta categoría.</li>
                    @endforelse
                </ul>
            </section>
        @empty
            <div class="card p-8 text-center text-sm text-gray-500">
                @if ($search || $status !== 'all')
                    No hay servicios que coincidan.
                @else
                    Primero creá una categoría en la pestaña <a href="{{ route('services.categories') }}" wire:navigate class="font-medium text-brand-700 underline">Categorías</a>.
                @endif
            </div>
        @endforelse
    </div>

    {{-- Modal --}}
    <div x-data="{ open: $wire.entangle('showModal') }" x-show="open" x-cloak x-on:keydown.escape.window="open = false" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div x-show="open" x-transition.opacity x-on:click="open = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="open" x-transition class="relative max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-6 shadow-xl sm:max-w-xl sm:rounded-2xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Editar servicio' : 'Nuevo servicio' }}</h2>
                <button type="button" x-on:click="open = false" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100" aria-label="Cerrar">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label" for="s-name">Nombre *</label>
                    <input id="s-name" type="text" wire:model="name" class="input">
                    @error('name') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="s-cat">Categoría *</label>
                    <select id="s-cat" wire:model="categoryId" class="input">
                        <option value="">Elegí una categoría</option>
                        @foreach ($allCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('categoryId') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="s-price">Precio *</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">$</span>
                            <input id="s-price" type="number" min="0" step="1" inputmode="numeric" wire:model="price" class="input pl-7">
                        </div>
                        @error('price') <p class="input-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="s-duration">Duración (min) *</label>
                        <input id="s-duration" type="number" min="5" max="600" step="5" inputmode="numeric" wire:model="duration" class="input">
                        @error('duration') <p class="input-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-wrap gap-1.5">
                    @foreach ($durations as $minutes)
                        <button type="button" wire:click="$set('duration', {{ $minutes }})"
                            @class(['badge cursor-pointer px-2.5 py-1', 'bg-brand-600 text-white' => (int) $duration === $minutes, 'bg-gray-100 text-gray-600 hover:bg-gray-200' => (int) $duration !== $minutes])>
                            {{ \App\Support\Time::durationLabel($minutes) }}
                        </button>
                    @endforeach
                </div>

                <div>
                    <label class="label" for="s-desc">Descripción</label>
                    <textarea id="s-desc" rows="2" wire:model="description" class="input"></textarea>
                    @error('description') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <span class="label mb-0">Profesionales que lo realizan</span>
                        @if ($professionals->isNotEmpty())
                            <button type="button" wire:click="toggleAllProfessionals" class="text-xs font-medium text-brand-700 hover:underline">Seleccionar todos</button>
                        @endif
                    </div>

                    @if ($professionals->isEmpty())
                        <p class="text-sm text-gray-500">Todavía no hay profesionales cargados.</p>
                    @else
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($professionals as $pro)
                                <label wire:key="sp-{{ $pro->id }}" class="flex items-center gap-2 rounded-lg px-3 py-2 ring-1 ring-gray-200 hover:bg-gray-50">
                                    <input type="checkbox" wire:model="professionalIds" value="{{ $pro->id }}" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                    <span class="h-3 w-3 rounded-full" style="background-color: {{ $pro->color }}"></span>
                                    <span class="text-sm">{{ $pro->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" wire:model="active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm text-gray-700">Activo (aparece al dar turnos)</span>
                </label>

                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" x-on:click="open = false" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold">Servicios</h1>
            <p class="text-sm text-gray-500">Categorías y su orden de aparición.</p>
        </div>
        <button type="button" wire:click="create" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Nueva categoría
        </button>
    </div>

    <x-services-tabs />

    <div class="card max-w-2xl divide-y divide-gray-100">
        @forelse ($categories as $category)
            <div wire:key="cat-{{ $category->id }}" @class(['flex items-center gap-3 px-4 py-3', 'opacity-60' => ! $category->active])>
                <div class="flex flex-col">
                    <button type="button" wire:click="move({{ $category->id }}, 'up')" @disabled($loop->first) class="rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-30" title="Subir">
                        <x-icon name="chevron-left" class="h-4 w-4 rotate-90" />
                    </button>
                    <button type="button" wire:click="move({{ $category->id }}, 'down')" @disabled($loop->last) class="rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-30" title="Bajar">
                        <x-icon name="chevron-right" class="h-4 w-4 rotate-90" />
                    </button>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium">{{ $category->name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $category->services_count }} {{ $category->services_count === 1 ? 'servicio' : 'servicios' }}
                        @unless ($category->active) · <span class="font-medium">Inactiva</span> @endunless
                    </p>
                </div>

                <button type="button" wire:click="edit({{ $category->id }})" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800" title="Editar">
                    <x-icon name="pencil" class="h-4 w-4" />
                </button>
                <button type="button" wire:click="delete({{ $category->id }})" wire:confirm="¿Eliminar la categoría {{ $category->name }}?" class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600" title="Eliminar">
                    <x-icon name="trash" class="h-4 w-4" />
                </button>
            </div>
        @empty
            <p class="p-6 text-center text-sm text-gray-500">Todavía no hay categorías.</p>
        @endforelse
    </div>

    {{-- Modal --}}
    <div x-data="{ open: $wire.entangle('showModal') }" x-show="open" x-cloak x-on:keydown.escape.window="open = false" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div x-show="open" x-transition.opacity x-on:click="open = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="open" x-transition class="relative w-full rounded-t-2xl bg-white p-6 shadow-xl sm:max-w-md sm:rounded-2xl">
            <h2 class="mb-5 text-lg font-semibold">{{ $editingId ? 'Editar categoría' : 'Nueva categoría' }}</h2>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label" for="c-name">Nombre *</label>
                    <input id="c-name" type="text" wire:model="name" class="input" autofocus>
                    @error('name') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" wire:model="active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm text-gray-700">Activa</span>
                </label>

                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" x-on:click="open = false" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

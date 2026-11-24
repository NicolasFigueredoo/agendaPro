<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold">Usuarios</h1>
            <p class="text-sm text-gray-500">Accesos al sistema y roles.</p>
        </div>
        <button type="button" wire:click="create" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Nuevo usuario
        </button>
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-gray-200 p-4">
            <div class="relative sm:max-w-sm">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o email" class="input pl-9">
            </div>
        </div>

        {{-- Escritorio --}}
        <table class="hidden min-w-full divide-y divide-gray-200 md:table">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">Nombre</th>
                    <th class="th">Email</th>
                    <th class="th">Teléfono</th>
                    <th class="th">Rol</th>
                    <th class="th">Estado</th>
                    <th class="th text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr wire:key="user-row-{{ $user->id }}" @class(['opacity-60' => ! $user->active])>
                        <td class="td font-medium text-gray-900">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="badge ml-1 bg-gray-100 text-gray-600">Vos</span>
                            @endif
                        </td>
                        <td class="td">{{ $user->email }}</td>
                        <td class="td">{{ $user->phone ?: '—' }}</td>
                        <td class="td"><span class="badge {{ $user->role->badgeClasses() }}">{{ $user->role->label() }}</span></td>
                        <td class="td">
                            <button type="button" wire:click="toggleActive({{ $user->id }})" @disabled($user->id === auth()->id())
                                @class(['badge', 'bg-emerald-100 text-emerald-700' => $user->active, 'bg-gray-200 text-gray-600' => ! $user->active])>
                                {{ $user->active ? 'Activo' : 'Inactivo' }}
                            </button>
                        </td>
                        <td class="td text-right">
                            <div class="inline-flex gap-1">
                                <button type="button" wire:click="edit({{ $user->id }})" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800" title="Editar">
                                    <x-icon name="pencil" class="h-4 w-4" />
                                </button>
                                @if ($user->id !== auth()->id())
                                    <button type="button" wire:click="delete({{ $user->id }})" wire:confirm="¿Eliminar a {{ $user->name }}? Si solo querés quitarle el acceso, desactivalo." class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600" title="Eliminar">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">No hay usuarios que coincidan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Celular --}}
        <ul class="divide-y divide-gray-100 md:hidden">
            @forelse ($users as $user)
                <li wire:key="user-card-{{ $user->id }}" @class(['flex items-center gap-3 p-4', 'opacity-60' => ! $user->active])>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ $user->name }}</p>
                        <p class="truncate text-sm text-gray-500">{{ $user->email }}</p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <span class="badge {{ $user->role->badgeClasses() }}">{{ $user->role->label() }}</span>
                            @unless ($user->active)
                                <span class="badge bg-gray-200 text-gray-600">Inactivo</span>
                            @endunless
                        </div>
                    </div>
                    <button type="button" wire:click="edit({{ $user->id }})" class="btn btn-secondary btn-sm">Editar</button>
                </li>
            @empty
                <li class="p-6 text-center text-sm text-gray-500">No hay usuarios que coincidan.</li>
            @endforelse
        </ul>

        @if ($users->hasPages())
            <div class="border-t border-gray-200 p-4">{{ $users->links() }}</div>
        @endif
    </div>

    {{-- Modal alta / edición (bottom sheet en celular) --}}
    <div
        x-data="{ open: $wire.entangle('showModal') }"
        x-show="open"
        x-cloak
        x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4"
    >
        <div x-show="open" x-transition.opacity x-on:click="open = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="open" x-transition class="relative max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-6 shadow-xl sm:max-w-lg sm:rounded-2xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
                <button type="button" x-on:click="open = false" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100" aria-label="Cerrar">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label" for="u-name">Nombre *</label>
                    <input id="u-name" type="text" wire:model="name" class="input" autocomplete="off">
                    @error('name') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="u-email">Email *</label>
                        <input id="u-email" type="email" wire:model="email" class="input" autocomplete="off" inputmode="email">
                        @error('email') <p class="input-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="u-phone">Teléfono</label>
                        <input id="u-phone" type="tel" wire:model="phone" class="input" placeholder="+54 9 11 ..." inputmode="tel">
                        @error('phone') <p class="input-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="u-role">Rol *</label>
                        <select id="u-role" wire:model="role" class="input">
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('role') <p class="input-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-gray-700">Usuario activo</span>
                        </label>
                    </div>
                </div>

                <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">
                    <strong>Admin:</strong> todo. <strong>Recepción:</strong> agenda, clientes y cobros. <strong>Profesional:</strong> solo ve su agenda y cambia el estado de sus turnos (se vincula al profesional desde Profesionales).
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="u-password">Contraseña {{ $editingId ? '' : '*' }}</label>
                        <input id="u-password" type="password" wire:model="password" class="input" autocomplete="new-password">
                        @if ($editingId)
                            <p class="mt-1 text-xs text-gray-500">Dejala vacía para no cambiarla.</p>
                        @endif
                        @error('password') <p class="input-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="u-password2">Repetir contraseña</label>
                        <input id="u-password2" type="password" wire:model="password_confirmation" class="input" autocomplete="new-password">
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" x-on:click="open = false" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php

use App\Livewire\Actions\Logout;
use App\Models\Location;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    public function with(): array
    {
        // Los ítems aparecen solos a medida que se crean las rutas de cada módulo.
        $sections = [
            'Operación' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'chart', 'can' => 'view-dashboard'],
                ['label' => 'Agenda', 'route' => 'agenda', 'active' => 'agenda*', 'icon' => 'calendar', 'can' => null],
                ['label' => 'Clientes', 'route' => 'clients.index', 'active' => 'clients.*', 'icon' => 'users', 'can' => 'manage-clients'],
                ['label' => 'Caja', 'route' => 'cash.index', 'active' => 'cash.*', 'icon' => 'cash', 'can' => 'manage-payments'],
            ],
            'Configuración' => [
                ['label' => 'Servicios', 'route' => 'services.index', 'active' => 'services.*', 'icon' => 'sparkles', 'can' => 'admin'],
                ['label' => 'Profesionales', 'route' => 'professionals.index', 'active' => 'professionals.*', 'icon' => 'user', 'can' => 'admin'],
                ['label' => 'Local', 'route' => 'location.edit', 'active' => 'location.*', 'icon' => 'building', 'can' => 'admin'],
                ['label' => 'Reglas y ajustes', 'route' => 'settings.index', 'active' => 'settings.*', 'icon' => 'adjustments', 'can' => 'admin'],
                ['label' => 'Usuarios', 'route' => 'admin.users', 'active' => 'admin.users*', 'icon' => 'shield', 'can' => 'admin'],
            ],
        ];

        $user = auth()->user();

        $sections = collect($sections)
            ->map(fn ($items) => collect($items)
                ->filter(fn ($item) => Route::has($item['route']) && (! $item['can'] || $user->can($item['can'])))
                ->values())
            ->filter(fn ($items) => $items->isNotEmpty());

        return [
            'sections' => $sections,
            'user' => $user,
            'locationName' => Location::current()->name,
        ];
    }
}; ?>

<div x-data="{ open: false }" x-on:keydown.escape.window="open = false">
    {{-- Barra superior (celular) --}}
    <div class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-gray-200 bg-white px-4 lg:hidden">
        <button type="button" x-on:click="open = true" class="-ml-2 rounded-lg p-2 text-gray-600 hover:bg-gray-100" aria-label="Abrir menú">
            <x-icon name="menu" class="h-6 w-6" />
        </button>
        <x-brand-mark class="h-8 w-8" />
        <span class="truncate font-semibold">{{ $locationName }}</span>
    </div>

    {{-- Fondo oscuro (celular) --}}
    <div x-show="open" x-transition.opacity x-cloak x-on:click="open = false" class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside
        :class="open ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:translate-x-0"
    >
        <div class="flex h-16 items-center justify-between gap-2 border-b border-gray-100 px-5">
            <a href="{{ route('home') }}" wire:navigate class="flex min-w-0 items-center gap-2">
                <x-brand-mark class="h-9 w-9 shrink-0" />
                <span class="truncate text-sm font-semibold">{{ $locationName }}</span>
            </a>
            <button type="button" x-on:click="open = false" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 lg:hidden" aria-label="Cerrar menú">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
            @foreach ($sections as $title => $items)
                <div>
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $title }}</p>
                    <ul class="space-y-1">
                        @foreach ($items as $item)
                            @php $isActive = request()->routeIs($item['active']); @endphp
                            <li>
                                <a
                                    href="{{ route($item['route']) }}"
                                    wire:navigate
                                    x-on:click="open = false"
                                    @class([
                                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                        'bg-brand-50 text-brand-700' => $isActive,
                                        'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => ! $isActive,
                                    ])
                                >
                                    <x-icon :name="$item['icon']" @class(['h-5 w-5', 'text-brand-600' => $isActive, 'text-gray-400' => ! $isActive]) />
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-gray-100 p-3">
            <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-50">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </span>
                <span class="min-w-0">
                    <span
                        class="block truncate text-sm font-medium text-gray-800"
                        x-data="{{ json_encode(['name' => $user->name]) }}"
                        x-text="name"
                        x-on:profile-updated.window="name = $event.detail.name"
                    ></span>
                    <span class="block truncate text-xs text-gray-500">{{ $user->role->label() }}</span>
                </span>
            </a>

            <button type="button" wire:click="logout" class="mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                <x-icon name="logout" class="h-5 w-5 text-gray-400" />
                Cerrar sesión
            </button>
        </div>
    </aside>
</div>

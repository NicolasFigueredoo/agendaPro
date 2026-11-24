@php
    $tabs = [
        ['route' => 'services.index', 'label' => 'Servicios'],
        ['route' => 'services.categories', 'label' => 'Categorías'],
        ['route' => 'services.prices', 'label' => 'Carga de precios'],
    ];
@endphp

<nav class="mb-6 flex gap-1 overflow-x-auto border-b border-gray-200">
    @foreach ($tabs as $tab)
        <a
            href="{{ route($tab['route']) }}"
            wire:navigate
            @class([
                '-mb-px whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium transition',
                'border-brand-600 text-brand-700' => request()->routeIs($tab['route']),
                'border-transparent text-gray-500 hover:text-gray-800' => ! request()->routeIs($tab['route']),
            ])
        >{{ $tab['label'] }}</a>
    @endforeach
</nav>

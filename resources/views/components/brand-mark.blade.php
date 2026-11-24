{{-- Logo del local, o un ícono de color si todavía no se cargó --}}
@php
    $location = \App\Models\Location::current();
    $logo = $location->logoUrl();
@endphp

@if ($logo)
    <img src="{{ $logo }}" alt="{{ $location->name }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
@else
    <span {{ $attributes->merge(['class' => 'flex items-center justify-center rounded-xl bg-brand-600 text-white']) }}>
        <x-icon name="sparkles" class="h-1/2 w-1/2" />
    </span>
@endif

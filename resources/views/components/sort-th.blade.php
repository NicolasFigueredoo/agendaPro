@props(['column', 'sort', 'direction'])

<th {{ $attributes->merge(['class' => 'th']) }}>
    <button type="button" wire:click="sortBy('{{ $column }}')" class="inline-flex items-center gap-1 uppercase hover:text-gray-800">
        {{ $slot }}
        @if ($sort === $column)
            <span class="text-brand-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
        @endif
    </button>
</th>

<div>
    <div class="mb-4">
        <h1 class="text-xl font-semibold">Servicios</h1>
        <p class="text-sm text-gray-500">Cambiá todos los precios de una y guardá al final.</p>
    </div>

    <x-services-tabs />

    {{-- Aumento general --}}
    <div class="card mb-6 p-4 sm:p-5">
        <h2 class="mb-3 font-semibold">Aplicar aumento</h2>
        <form wire:submit="applyIncrease" class="grid gap-3 sm:grid-cols-4 sm:items-end">
            <div>
                <label class="label" for="p-percent">Porcentaje</label>
                <div class="relative">
                    <input id="p-percent" type="number" step="0.5" wire:model="percent" class="input pr-8" placeholder="Ej: 15">
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">%</span>
                </div>
                @error('percent') <p class="input-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="p-round">Redondear a</label>
                <select id="p-round" wire:model="rounding" class="input">
                    <option value="1">Sin redondeo</option>
                    <option value="10">$ 10</option>
                    <option value="50">$ 50</option>
                    <option value="100">$ 100</option>
                    <option value="500">$ 500</option>
                    <option value="1000">$ 1.000</option>
                </select>
            </div>
            <div>
                <label class="label" for="p-scope">Aplicar a</label>
                <select id="p-scope" wire:model="scope" class="input">
                    <option value="">Todas las categorías</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Calcular</button>
        </form>
        <p class="mt-2 text-xs text-gray-500">Se calcula en pantalla. No se guarda hasta que toques "Guardar precios".</p>
    </div>

    {{-- Tabla editable --}}
    <form wire:submit="save" class="space-y-6 pb-24">
        @foreach ($categories as $category)
            @continue($category->services->isEmpty())

            <section wire:key="pcat-{{ $category->id }}" class="card overflow-hidden">
                <header class="border-b border-gray-200 bg-gray-50 px-4 py-3 font-semibold">{{ $category->name }}</header>

                <ul class="divide-y divide-gray-100">
                    @foreach ($category->services as $service)
                        @php
                            $old = (int) ($original[$service->id] ?? 0);
                            $new = (int) ($prices[$service->id] ?? 0);
                        @endphp
                        <li wire:key="price-{{ $service->id }}" @class(['flex items-center gap-3 px-4 py-2.5', 'opacity-60' => ! $service->active])>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $service->name }}</p>
                                <p class="text-xs text-gray-500">
                                    Actual: @ars($old)
                                    @if ($old !== $new && $old > 0)
                                        @php $diff = round((($new - $old) / $old) * 100, 1); @endphp
                                        <span @class(['font-medium', 'text-emerald-600' => $diff > 0, 'text-red-600' => $diff < 0])>
                                            ({{ $diff > 0 ? '+' : '' }}{{ str_replace('.', ',', (string) $diff) }}%)
                                        </span>
                                    @endif
                                </p>
                            </div>

                            <div class="w-32 shrink-0 sm:w-40">
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">$</span>
                                    <input type="number" min="0" step="1" inputmode="numeric" wire:model.blur="prices.{{ $service->id }}"
                                        @class(['input pl-7 text-right', 'border-amber-400 bg-amber-50' => $old !== $new])>
                                </div>
                                @error("prices.{$service->id}") <p class="input-error text-right">{{ $message }}</p> @enderror
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        {{-- Barra fija de guardado --}}
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-gray-200 bg-white/95 px-4 py-3 backdrop-blur lg:pl-72">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-gray-600">
                    @if ($dirty)
                        <span class="font-semibold text-amber-700">{{ $dirty }}</span> {{ $dirty === 1 ? 'precio modificado' : 'precios modificados' }}
                    @else
                        Sin cambios
                    @endif
                </p>
                <div class="flex gap-2">
                    @if ($dirty)
                        <button type="button" wire:click="discard" class="btn btn-secondary">Descartar</button>
                    @endif
                    <button type="submit" class="btn btn-primary" @disabled(! $dirty) wire:loading.attr="disabled" wire:target="save">Guardar precios</button>
                </div>
            </div>
        </div>
    </form>
</div>

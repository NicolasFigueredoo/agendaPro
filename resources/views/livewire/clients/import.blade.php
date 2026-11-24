<div>
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('clients.index') }}" wire:navigate class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Volver">
            <x-icon name="chevron-left" class="h-5 w-5" />
        </a>
        <div>
            <h1 class="text-xl font-semibold">Importar clientes</h1>
            <p class="text-sm text-gray-500">Desde Excel (.xlsx, .xls) o CSV. Ej: la exportación de clientes de AgendaPro.</p>
        </div>
    </div>

    {{-- Pasos --}}
    <ol class="mb-6 flex items-center gap-2 text-sm">
        @foreach ([1 => 'Archivo', 2 => 'Columnas', 3 => 'Resultado'] as $n => $label)
            <li class="flex items-center gap-2">
                <span @class([
                    'flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold',
                    'bg-brand-600 text-white' => $step >= $n,
                    'bg-gray-200 text-gray-500' => $step < $n,
                ])>{{ $n }}</span>
                <span @class(['font-medium', 'text-gray-900' => $step >= $n, 'text-gray-400' => $step < $n])>{{ $label }}</span>
                @unless ($loop->last) <span class="mx-1 h-px w-6 bg-gray-300"></span> @endunless
            </li>
        @endforeach
    </ol>

    {{-- Paso 1 --}}
    @if ($step === 1)
        <div class="card max-w-xl p-6">
            <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 px-6 py-10 text-center hover:border-brand-400 hover:bg-brand-50/40">
                <x-icon name="users" class="mb-3 h-10 w-10 text-gray-300" />
                <span class="font-medium text-gray-700">Elegí el archivo</span>
                <span class="mt-1 text-xs text-gray-500">La primera fila tiene que tener los nombres de las columnas. Máx. 10 MB.</span>
                <input type="file" wire:model="file" accept=".xlsx,.xls,.csv,.txt" class="sr-only">
            </label>

            <div wire:loading wire:target="file" class="mt-3 text-sm text-gray-500">Leyendo archivo…</div>
            @error('file') <p class="input-error mt-3">{{ $message }}</p> @enderror
        </div>
    @endif

    {{-- Paso 2 --}}
    @if ($step === 2)
        <div class="space-y-6">
            <div class="card p-5 sm:p-6">
                <h2 class="mb-1 font-semibold">Asigná las columnas</h2>
                <p class="mb-4 text-sm text-gray-500">
                    Encontramos <strong>{{ $totalRows }}</strong> filas. Ya adivinamos lo que pudimos; revisá y corregí.
                </p>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($fields as $field => $label)
                        <div wire:key="map-{{ $field }}">
                            <label class="label">{{ $label }}</label>
                            <select wire:model="mapping.{{ $field }}" class="input">
                                <option value="">— No importar —</option>
                                @foreach ($headers as $index => $header)
                                    <option value="{{ $index }}">{{ $header !== '' ? $header : 'Columna '.($index + 1) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>

                @error('mapping') <p class="input-error mt-3">{{ $message }}</p> @enderror
            </div>

            <div class="card p-5 sm:p-6">
                <h2 class="mb-3 font-semibold">Clientes repetidos</h2>
                <p class="mb-3 text-sm text-gray-500">Se considera repetido si ya existe un cliente con el mismo DNI o el mismo teléfono.</p>
                <div class="space-y-2">
                    <label class="flex items-start gap-2">
                        <input type="radio" wire:model="mode" value="skip" class="mt-0.5 border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm"><strong>Saltearlos</strong> — no se toca el cliente existente.</span>
                    </label>
                    <label class="flex items-start gap-2">
                        <input type="radio" wire:model="mode" value="update" class="mt-0.5 border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm"><strong>Actualizarlos</strong> — se completan con los datos del archivo (los campos vacíos del archivo no borran nada).</span>
                    </label>
                </div>
            </div>

            {{-- Vista previa --}}
            <div class="card overflow-hidden">
                <h2 class="border-b border-gray-200 px-5 py-3 font-semibold">Vista previa (primeras filas)</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach ($headers as $header)
                                    <th class="th whitespace-nowrap">{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($preview as $row)
                                <tr>
                                    @foreach ($headers as $index => $header)
                                        <td class="td">{{ $row[$index] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="restart" class="btn btn-secondary">Elegir otro archivo</button>
                <button type="button" wire:click="import" class="btn btn-primary" wire:loading.attr="disabled" wire:target="import">
                    <span wire:loading.remove wire:target="import">Importar {{ $totalRows }} filas</span>
                    <span wire:loading wire:target="import">Importando…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Paso 3 --}}
    @if ($step === 3)
        <div class="max-w-2xl space-y-6">
            <div class="grid grid-cols-3 gap-3">
                <div class="card p-4 text-center">
                    <p class="text-2xl font-semibold text-emerald-600">{{ $result['created'] }}</p>
                    <p class="text-xs text-gray-500">Creados</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-2xl font-semibold text-sky-600">{{ $result['updated'] }}</p>
                    <p class="text-xs text-gray-500">Actualizados</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-2xl font-semibold text-gray-600">{{ $result['skipped'] }}</p>
                    <p class="text-xs text-gray-500">Repetidos salteados</p>
                </div>
            </div>

            @if (count($result['errors']))
                <div class="card overflow-hidden">
                    <h2 class="border-b border-gray-200 bg-red-50 px-5 py-3 font-semibold text-red-800">{{ count($result['errors']) }} filas con errores (no se importaron)</h2>
                    <ul class="max-h-80 divide-y divide-gray-100 overflow-y-auto text-sm">
                        @foreach ($result['errors'] as $error)
                            <li class="px-5 py-2"><span class="font-medium">Fila {{ $error['row'] }}:</span> {{ $error['message'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="restart" class="btn btn-secondary">Importar otro archivo</button>
                <a href="{{ route('clients.index') }}" wire:navigate class="btn btn-primary">Ver clientes</a>
            </div>
        </div>
    @endif
</div>

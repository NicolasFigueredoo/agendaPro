<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold">Local</h1>
        <p class="text-sm text-gray-500">Datos que aparecen en los mails, en las páginas de pago y en el login.</p>
    </div>

    <form wire:submit="save" class="max-w-4xl space-y-6">
        {{-- Datos --}}
        <div class="card p-5 sm:p-6">
            <h2 class="mb-4 font-semibold">Datos del local</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label" for="l-name">Nombre *</label>
                    <input id="l-name" type="text" wire:model="name" class="input">
                    @error('name') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="label" for="l-address">Dirección</label>
                    <input id="l-address" type="text" wire:model="address" class="input" placeholder="Calle 123, Localidad">
                    @error('address') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="l-phone">Teléfono / WhatsApp</label>
                    <input id="l-phone" type="tel" wire:model="phone" class="input" placeholder="+54 9 11 1234-5678" inputmode="tel">
                    @error('phone') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="l-email">Email</label>
                    <input id="l-email" type="email" wire:model="email" class="input" inputmode="email">
                    @error('email') <p class="input-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Logo --}}
        <div class="card p-5 sm:p-6">
            <h2 class="mb-4 font-semibold">Logo</h2>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gray-50 ring-1 ring-gray-200">
                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" class="h-full w-full object-contain" alt="Vista previa">
                    @elseif ($currentLogoUrl)
                        <img src="{{ $currentLogoUrl }}" class="h-full w-full object-contain" alt="Logo actual">
                    @else
                        <x-icon name="sparkles" class="h-8 w-8 text-gray-300" />
                    @endif
                </div>

                <div class="space-y-2">
                    <input type="file" wire:model="logo" accept="image/*" class="block text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                    <p class="text-xs text-gray-500">PNG o JPG, hasta 2 MB. Ideal cuadrado y con fondo transparente.</p>
                    <div wire:loading wire:target="logo" class="text-xs text-gray-500">Subiendo…</div>
                    @error('logo') <p class="input-error">{{ $message }}</p> @enderror

                    @if ($currentLogoUrl && ! $logo)
                        <button type="button" wire:click="removeLogo" wire:confirm="¿Quitar el logo?" class="text-xs font-medium text-red-600 hover:underline">Quitar logo</button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Horario --}}
        <div class="card p-5 sm:p-6">
            <h2 class="mb-1 font-semibold">Horario de atención</h2>
            <p class="mb-4 text-sm text-gray-500">Es informativo para los clientes. La disponibilidad de la agenda sale del horario de cada profesional.</p>

            <div class="divide-y divide-gray-100">
                @foreach ($weekdays as $day => $dayName)
                    <div wire:key="hour-{{ $day }}" class="flex flex-wrap items-center gap-3 py-3">
                        <label class="flex w-32 items-center gap-2">
                            <input type="checkbox" wire:model.live="hours.{{ $day }}.active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-sm font-medium">{{ $dayName }}</span>
                        </label>

                        @if ($hours[$day]['active'])
                            <div class="flex items-center gap-2">
                                <input type="time" step="900" wire:model="hours.{{ $day }}.start" class="input w-28">
                                <span class="text-sm text-gray-400">a</span>
                                <input type="time" step="900" wire:model="hours.{{ $day }}.end" class="input w-28">
                            </div>
                            <button type="button" wire:click="copyToAll({{ $day }})" class="text-xs font-medium text-brand-700 hover:underline">Copiar en todos</button>
                        @else
                            <span class="text-sm text-gray-400">Cerrado</span>
                        @endif

                        @error("hours.$day.start") <p class="input-error w-full">{{ $message }}</p> @enderror
                        @error("hours.$day.end") <p class="input-error w-full">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary w-full sm:w-auto" wire:loading.attr="disabled" wire:target="save,logo">Guardar cambios</button>
        </div>
    </form>
</div>

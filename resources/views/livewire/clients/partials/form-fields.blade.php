{{--
    Campos del formulario de cliente. Requiere en el componente: public ClientForm $form;
    Variables: $genders, $provinces
--}}
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label" for="cf-first">Nombre *</label>
        <input id="cf-first" type="text" wire:model="form.first_name" class="input" autocomplete="off">
        @error('form.first_name') <p class="input-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="cf-last">Apellido *</label>
        <input id="cf-last" type="text" wire:model="form.last_name" class="input" autocomplete="off">
        @error('form.last_name') <p class="input-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="label" for="cf-phone">Teléfono</label>
        <div class="flex">
            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">+54</span>
            <input id="cf-phone" type="tel" wire:model="form.phone" class="input rounded-l-none" placeholder="11 1234-5678" inputmode="tel">
        </div>
        <p class="mt-1 text-xs text-gray-400">Código de área + número, sin 0 ni 15.</p>
        @error('form.phone') <p class="input-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="cf-email">Email</label>
        <input id="cf-email" type="email" wire:model="form.email" class="input" inputmode="email">
        @error('form.email') <p class="input-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="label" for="cf-dni">DNI</label>
        <input id="cf-dni" type="text" wire:model="form.dni" class="input" inputmode="numeric">
        @error('form.dni') <p class="input-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="cf-birth">Fecha de nacimiento</label>
        <input id="cf-birth" type="date" wire:model="form.birth_date" class="input" max="{{ now()->toDateString() }}">
        @error('form.birth_date') <p class="input-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="label" for="cf-gender">Género</label>
        <select id="cf-gender" wire:model="form.gender" class="input">
            <option value="">—</option>
            @foreach ($genders as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('form.gender') <p class="input-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="cf-number">Número de cliente</label>
        <input id="cf-number" type="number" min="1" wire:model="form.client_number" class="input" placeholder="Automático">
        @error('form.client_number') <p class="input-error">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="label" for="cf-address">Dirección</label>
        <input id="cf-address" type="text" wire:model="form.address" class="input">
        @error('form.address') <p class="input-error">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="label" for="cf-province">Provincia</label>
        <select id="cf-province" wire:model="form.province" class="input">
            <option value="">—</option>
            @if ($form->province && ! in_array($form->province, $provinces, true))
                <option value="{{ $form->province }}">{{ $form->province }}</option>
            @endif
            @foreach ($provinces as $province)
                <option value="{{ $province }}">{{ $province }}</option>
            @endforeach
        </select>
        @error('form.province') <p class="input-error">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="label" for="cf-notes">Notas</label>
        <textarea id="cf-notes" rows="3" wire:model="form.notes" class="input" placeholder="Alergias, preferencias, etc."></textarea>
        @error('form.notes') <p class="input-error">{{ $message }}</p> @enderror
    </div>
</div>

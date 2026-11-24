<div>
    @if (! $enabled)
        <div class="card p-8 text-center">
            <p class="text-lg font-semibold">Las reservas online no están disponibles en este momento</p>
            <p class="mt-2 text-sm text-gray-500">Escribinos por WhatsApp para sacar tu turno.</p>
            @if ($wa = \App\Support\Phone::whatsappUrl(\App\Models\Location::current()->phone, 'Hola! Quiero sacar un turno'))
                <a href="{{ $wa }}" class="btn btn-primary mt-5">Escribir por WhatsApp</a>
            @endif
        </div>
    @else
        {{-- Progreso --}}
        @if ($step <= 5)
            <ol class="mb-5 flex items-center gap-1.5">
                @foreach ([1 => 'Servicio', 2 => 'Profesional', 3 => 'Horario', 4 => 'Tus datos', 5 => 'Pago'] as $n => $label)
                    <li class="flex-1">
                        <button type="button" wire:click="goTo({{ $n }})" @disabled($n >= $step) class="w-full text-left">
                            <span @class(['block h-1.5 rounded-full', 'bg-brand-600' => $n <= $step, 'bg-gray-200' => $n > $step])></span>
                            <span @class(['mt-1 hidden text-xs sm:block', 'font-medium text-brand-700' => $n === $step, 'text-gray-400' => $n !== $step])>{{ $label }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>
        @endif

        {{-- Resumen de lo elegido --}}
        @if ($this->service && $step > 1 && $step <= 5)
            <div class="card mb-4 flex items-center justify-between gap-3 px-4 py-3">
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ $this->service->name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $this->service->durationLabel() }} · @ars($this->service->price)
                        @if ($date && $time && $step >= 4)
                            · {{ ucfirst(\Carbon\Carbon::parse($date)->translatedFormat('D j/m')) }} {{ $time }} hs
                        @endif
                    </p>
                </div>
                <button type="button" wire:click="goTo(1)" class="shrink-0 text-xs font-medium text-brand-700 hover:underline">Cambiar</button>
            </div>
        @endif

        {{-- 1. Servicio --}}
        @if ($step === 1)
            <h1 class="mb-1 text-xl font-semibold">¿Qué te querés hacer?</h1>
            <p class="mb-5 text-sm text-gray-500">Elegí el servicio. Para confirmar el turno se abona una seña ({{ $depositDescription }}).</p>

            <div class="space-y-5">
                @forelse ($categories as $category)
                    <section wire:key="pcat-{{ $category->id }}" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="card overflow-hidden">
                        <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between px-4 py-3 text-left">
                            <span class="font-semibold">{{ $category->name }}</span>
                            <x-icon name="chevron-right" class="h-4 w-4 text-gray-400 transition" ::class="open && 'rotate-90'" />
                        </button>
                        <ul x-show="open" x-collapse class="divide-y divide-gray-100 border-t border-gray-100">
                            @foreach ($category->services as $service)
                                <li wire:key="psrv-{{ $service->id }}">
                                    <button type="button" wire:click="selectService({{ $service->id }})" class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-brand-50/50 active:bg-brand-50">
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-medium">{{ $service->name }}</span>
                                            <span class="block text-xs text-gray-500">{{ $service->durationLabel() }}</span>
                                        </span>
                                        <span class="text-sm font-semibold">@ars($service->price)</span>
                                        <x-icon name="chevron-right" class="h-4 w-4 text-gray-300" />
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @empty
                    <div class="card p-6 text-center text-sm text-gray-500">No hay servicios disponibles para reservar online.</div>
                @endforelse
            </div>
        @endif

        {{-- 2. Profesional --}}
        @if ($step === 2)
            <h1 class="mb-4 text-xl font-semibold">¿Con quién?</h1>

            <div class="grid gap-3 sm:grid-cols-2">
                <button type="button" wire:click="selectProfessional('any')" class="card flex items-center gap-3 p-4 text-left hover:ring-brand-300">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-500"><x-icon name="users" class="h-5 w-5" /></span>
                    <span>
                        <span class="block font-medium">Cualquiera</span>
                        <span class="block text-xs text-gray-500">Te mostramos todos los horarios</span>
                    </span>
                </button>

                @foreach ($this->professionals as $pro)
                    <button type="button" wire:key="ppro-{{ $pro->id }}" wire:click="selectProfessional('{{ $pro->id }}')" class="card flex items-center gap-3 p-4 text-left hover:ring-brand-300">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full text-lg font-semibold text-white" style="background-color: {{ $pro->color }}">{{ mb_strtoupper(mb_substr($pro->name, 0, 1)) }}</span>
                        <span class="font-medium">{{ $pro->name }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        {{-- 3. Día y hora --}}
        @if ($step === 3)
            <div class="mb-4 flex items-center justify-between gap-2">
                <h1 class="text-xl font-semibold">Elegí día y hora</h1>
                @if ($this->professionals->count() > 1)
                    <button type="button" wire:click="goTo(2)" class="text-xs font-medium text-brand-700 hover:underline">
                        {{ $selectedProfessional ? 'Con '.$selectedProfessional->name : 'Con cualquiera' }} · cambiar
                    </button>
                @endif
            </div>

            <div class="card p-4">
                <div class="mb-3 flex items-center justify-between">
                    <button type="button" wire:click="moreDays(-1)" @disabled(! $canGoBack) class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 disabled:opacity-30" aria-label="Días anteriores">
                        <x-icon name="chevron-left" class="h-5 w-5" />
                    </button>
                    <p class="text-sm font-medium capitalize text-gray-600">{{ \Carbon\Carbon::today()->addDays($dayOffset)->translatedFormat('F Y') }}</p>
                    <button type="button" wire:click="moreDays(1)" @disabled(! $canGoForward) class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 disabled:opacity-30" aria-label="Días siguientes">
                        <x-icon name="chevron-right" class="h-5 w-5" />
                    </button>
                </div>

                <div class="grid grid-cols-7 gap-1.5" wire:loading.class="opacity-50" wire:target="moreDays">
                    @foreach ($this->days as $day)
                        <button type="button" wire:key="day-{{ $day['date'] }}"
                            wire:click="selectDate('{{ $day['date'] }}')"
                            @disabled(! $day['available'])
                            @class([
                                'flex flex-col items-center rounded-xl py-2 text-center transition',
                                'bg-brand-600 text-white shadow' => $date === $day['date'],
                                'bg-white ring-1 ring-gray-200 hover:ring-brand-400' => $date !== $day['date'] && $day['available'],
                                'cursor-not-allowed bg-gray-50 text-gray-300' => ! $day['available'],
                            ])>
                            <span class="text-[11px] uppercase">{{ $day['label'] }}</span>
                            <span class="text-lg font-semibold leading-tight">{{ $day['number'] }}</span>
                        </button>
                    @endforeach
                </div>

                @if ($date)
                    <div class="mt-5 border-t border-gray-100 pt-4" wire:loading.class="opacity-50" wire:target="selectDate">
                        <p class="mb-3 text-sm font-medium">{{ ucfirst(\Carbon\Carbon::parse($date)->translatedFormat('l j \d\e F')) }}</p>
                        @if (count($this->slots))
                            <div class="grid grid-cols-4 gap-2 sm:grid-cols-6">
                                @foreach ($this->slots as $slot)
                                    <button type="button" wire:key="slot-{{ $slot }}" wire:click="selectTime('{{ $slot }}')" class="rounded-lg bg-white py-2 text-sm font-medium ring-1 ring-gray-200 hover:bg-brand-50 hover:ring-brand-400">
                                        {{ $slot }}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No quedan horarios ese día.</p>
                        @endif
                    </div>
                @else
                    <p class="mt-4 text-center text-sm text-gray-500">Elegí un día para ver los horarios.</p>
                @endif
            </div>
        @endif

        {{-- 4. Datos --}}
        @if ($step === 4)
            <h1 class="mb-4 text-xl font-semibold">Tus datos</h1>

            <form wire:submit="toSummary" class="card space-y-4 p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="pb-first">Nombre *</label>
                        <input id="pb-first" type="text" wire:model="first_name" class="input" autocomplete="given-name">
                        @error('first_name') <p class="input-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="pb-last">Apellido *</label>
                        <input id="pb-last" type="text" wire:model="last_name" class="input" autocomplete="family-name">
                        @error('last_name') <p class="input-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="label" for="pb-phone">Celular (WhatsApp) *</label>
                    <div class="flex">
                        <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">+54</span>
                        <input id="pb-phone" type="tel" wire:model="phone" class="input rounded-l-none" placeholder="11 1234-5678" inputmode="tel" autocomplete="tel-national">
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Código de área + número, sin 0 ni 15.</p>
                    @error('phone') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="pb-email">Email *</label>
                    <input id="pb-email" type="email" wire:model="email" class="input" inputmode="email" autocomplete="email">
                    <p class="mt-1 text-xs text-gray-400">Te mandamos la confirmación del turno.</p>
                    @error('email') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="pb-notes">Comentarios</label>
                    <textarea id="pb-notes" rows="2" wire:model="notes" class="input" placeholder="Opcional"></textarea>
                    @error('notes') <p class="input-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-between">
                    <button type="button" wire:click="goTo(3)" class="btn btn-secondary">Volver</button>
                    <button type="submit" class="btn btn-primary">Continuar</button>
                </div>
            </form>
        @endif

        {{-- 5. Resumen y pago --}}
        @if ($step === 5)
            <h1 class="mb-4 text-xl font-semibold">Revisá y pagá la seña</h1>

            <div class="card divide-y divide-gray-100">
                <dl class="space-y-2 p-5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Servicio</dt><dd class="text-right font-medium">{{ $this->service->name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Día</dt><dd class="text-right font-medium">{{ ucfirst(\Carbon\Carbon::parse($date)->translatedFormat('l j \d\e F')) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Hora</dt><dd class="text-right font-medium">{{ $time }} hs</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Con</dt><dd class="text-right font-medium">{{ $selectedProfessional?->name ?? 'La primera disponible' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">A nombre de</dt><dd class="text-right font-medium">{{ $first_name }} {{ $last_name }}</dd></div>
                </dl>
                <dl class="space-y-2 p-5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Precio del servicio</dt><dd>@ars($this->service->price)</dd></div>
                    <div class="flex justify-between gap-4 text-base"><dt class="font-semibold">Seña a pagar ahora</dt><dd class="font-semibold text-brand-700">@ars($deposit)</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Resta abonar en el local</dt><dd>@ars($this->service->price - $deposit)</dd></div>
                </dl>
                <div class="space-y-4 p-5">
                    <p class="rounded-lg bg-gray-50 p-3 text-xs text-gray-600">{{ $terms }}</p>

                    <label class="flex items-start gap-2">
                        <input type="checkbox" wire:model="acceptTerms" class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm">Acepto las condiciones de la seña.</span>
                    </label>
                    @error('acceptTerms') <p class="input-error">{{ $message }}</p> @enderror
                    @error('confirm') <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p> @enderror

                    <button type="button" wire:click="confirm" wire:loading.attr="disabled" wire:target="confirm" class="btn w-full bg-[#009ee3] py-3 text-base text-white hover:bg-[#0089c7] focus:ring-[#009ee3]">
                        <span wire:loading.remove wire:target="confirm">Pagar seña con Mercado Pago</span>
                        <span wire:loading wire:target="confirm">Reservando tu horario…</span>
                    </button>
                    <p class="text-center text-xs text-gray-500">Te guardamos el horario {{ $holdMinutes }} minutos mientras pagás.</p>

                    <button type="button" wire:click="goTo(4)" class="block w-full text-center text-sm text-gray-500 hover:text-gray-800">Volver</button>
                </div>
            </div>
        @endif

        {{-- 6. Confirmado sin seña --}}
        @if ($step === 6)
            <div class="card p-8 text-center">
                <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><x-icon name="check" class="h-7 w-7" /></span>
                <p class="text-lg font-semibold">¡Listo, tu turno quedó reservado!</p>
                <p class="mt-2 text-sm text-gray-500">{{ $this->service?->name }} · {{ $date ? ucfirst(\Carbon\Carbon::parse($date)->translatedFormat('l j/m')) : '' }} {{ $time }} hs</p>
            </div>
        @endif
    @endif
</div>

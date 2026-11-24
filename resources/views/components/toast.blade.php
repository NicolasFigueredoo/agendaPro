{{--
    Desde Livewire: $this->dispatch('toast', message: 'Guardado', type: 'success'|'error')
    Después de un redirect: session()->flash('toast', 'Guardado')
--}}
<div
    x-data="{ show: false, message: @js(session('toast')), type: 'success', timer: null }"
    x-init="if (message) { show = true; timer = setTimeout(() => show = false, 3500) }"
    x-on:toast.window="
        message = $event.detail.message;
        type = $event.detail.type ?? 'success';
        show = true;
        clearTimeout(timer);
        timer = setTimeout(() => show = false, 3500);
    "
    x-show="show"
    x-transition.opacity.duration.200ms
    x-cloak
    class="fixed inset-x-4 bottom-4 z-[60] sm:left-auto sm:right-6 sm:w-96"
    role="status"
>
    <div
        :class="type === 'error' ? 'bg-red-600' : 'bg-gray-900'"
        class="flex items-start gap-3 rounded-xl px-4 py-3 text-sm text-white shadow-lg"
    >
        <span class="flex-1" x-text="message"></span>
        <button type="button" x-on:click="show = false" class="opacity-70 hover:opacity-100" aria-label="Cerrar">
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>
</div>

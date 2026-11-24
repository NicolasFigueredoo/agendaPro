<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('home', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="mb-1 text-lg font-semibold">Ingresar</h1>
    <p class="mb-5 text-sm text-gray-500">Usá el email y la contraseña que te dio el administrador.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="label">Email</label>
            <input wire:model="form.email" id="email" type="email" name="email" class="input" required autofocus autocomplete="username" inputmode="email">
            @error('form.email') <p class="input-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="label">Contraseña</label>
            <input wire:model="form.password" id="password" type="password" name="password" class="input" required autocomplete="current-password">
            @error('form.password') <p class="input-error">{{ $message }}</p> @enderror
        </div>

        <label for="remember" class="inline-flex items-center gap-2">
            <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500" name="remember">
            <span class="text-sm text-gray-600">Mantener la sesión iniciada</span>
        </label>

        <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">Ingresar</span>
            <span wire:loading wire:target="login">Ingresando…</span>
        </button>

        @if (Route::has('password.request'))
            <p class="text-center">
                <a class="text-sm text-gray-500 underline hover:text-gray-800" href="{{ route('password.request') }}" wire:navigate>
                    ¿Olvidaste tu contraseña?
                </a>
            </p>
        @endif
    </form>
</div>

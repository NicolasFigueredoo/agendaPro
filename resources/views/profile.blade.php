<x-app-layout title="Mi perfil">
    <div class="mb-6">
        <h1 class="text-xl font-semibold">Mi perfil</h1>
        <p class="text-sm text-gray-500">Tus datos de acceso.</p>
    </div>

    <div class="max-w-3xl space-y-6">
        <div class="card p-4 sm:p-8">
            <div class="max-w-xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </div>

        <div class="card p-4 sm:p-8">
            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>
        </div>

        {{-- Sin "Eliminar cuenta": los usuarios los gestiona el admin. --}}
    </div>
</x-app-layout>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#e11d48">

        <title>{{ \App\Models\Location::current()->name }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-brand-50 via-white to-brand-100 px-4 py-10">
            <a href="/" wire:navigate class="mb-6 flex flex-col items-center text-center">
                <x-brand-mark class="h-16 w-16" />
                <p class="mt-3 text-lg font-semibold text-gray-800">{{ \App\Models\Location::current()->name }}</p>
            </a>

            <div class="card w-full px-6 py-6 sm:max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>

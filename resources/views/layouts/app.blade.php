<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#e11d48">

        <title>{{ isset($title) && $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 font-sans text-gray-900 antialiased">
        <div class="min-h-screen">
            <livewire:layout.navigation />

            <div class="lg:pl-64">
                @isset($header)
                    <header class="border-b border-gray-200 bg-white">
                        <div class="px-4 py-4 sm:px-6">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="px-4 py-6 sm:px-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-toast />
    </body>
</html>

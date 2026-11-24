@php $location = \App\Models\Location::current(); @endphp
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#e11d48">
        <meta name="robots" content="noindex">

        <title>{{ isset($title) && $title ? $title.' · ' : '' }}{{ $location->name }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gradient-to-b from-brand-50 to-white font-sans text-gray-900 antialiased">
        <header class="mx-auto flex max-w-2xl items-center gap-3 px-4 pb-2 pt-6">
            <x-brand-mark class="h-12 w-12 shrink-0" />
            <div class="min-w-0">
                <p class="truncate text-lg font-semibold">{{ $location->name }}</p>
                @if ($location->address)
                    <p class="truncate text-xs text-gray-500">{{ $location->address }}</p>
                @endif
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-4 pb-16 pt-4">
            {{ $slot }}
        </main>

        <x-toast />
    </body>
</html>

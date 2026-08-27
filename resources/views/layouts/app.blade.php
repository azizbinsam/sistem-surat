<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-zinc-50">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        <livewire:layout.navigation />

        <div class="lg:pl-64 flex flex-col min-h-screen">
            <div
                class="sticky top-0 z-30 flex items-center justify-between gap-3 bg-white border-b border-zinc-200 px-4 py-3">
                <div class="flex items-center gap-3 lg:hidden">
                    <button @click="sidebarOpen = true" class="text-zinc-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <span class="font-semibold text-zinc-900">{{ config('app.name') }}</span>
                </div>
                <div class="hidden lg:block"></div>
                <livewire:layout.topbar />
            </div>

            @if (isset($header))
                <header class="bg-white border-b border-zinc-200">
                    <div class="px-4 sm:px-6 lg:px-8 py-6">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <main class="flex-1 px-4 sm:px-6 lg:px-8 py-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>

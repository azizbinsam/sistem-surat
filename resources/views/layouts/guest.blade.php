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
    <body class="font-sans antialiased">
        <div class="min-h-screen flex">
            {{-- panel kiri, brand (hidden di mobile) --}}
            <div class="hidden lg:flex lg:w-1/2 bg-zinc-900 flex-col justify-between p-12">
                <a href="{{ route('welcome') }}" class="text-white font-bold text-xl">
                    {{ config('app.name') }}
                </a>
                <div>
                    <h2 class="text-white text-3xl font-bold leading-tight mb-4">
                        Generate Surat NPB, SPB, SPPB<br>Dalam Hitungan Detik.
                    </h2>
                    <p class="text-zinc-400">Upload Excel, sistem urus penomoran, ledger stok, sampai dokumen siap tanda tangan.</p>
                </div>
                <p class="text-zinc-600 text-sm">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            </div>

            {{-- panel kanan, form --}}
            <div class="flex-1 flex items-center justify-center p-6 bg-zinc-50">
                <div class="w-full max-w-sm">
                    <div class="lg:hidden mb-8 text-center">
                        <span class="font-bold text-xl text-zinc-900">{{ config('app.name') }}</span>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-zinc-100 p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Eko-Scribe') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-700">
<div class="landing-bg min-h-screen flex flex-col items-center justify-center p-4">
    <a href="{{ url('/') }}" class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 grid place-items-center shadow-lg">
            <x-icon name="leaf" class="w-7 h-7 text-white"/>
        </div>
        <div class="text-left">
            <p class="text-2xl font-bold text-slate-800 leading-none">Eko-Scribe</p>
            <p class="text-[10px] uppercase tracking-wider text-emerald-600 font-semibold">v{{ config('app.version') }}</p>
        </div>
    </a>

    <div class="w-full sm:max-w-md glass-strong p-6 sm:p-8">
        {{ $slot }}
    </div>

    <p class="mt-6 text-xs text-slate-500">© {{ now()->year }} Eko-Scribe • Pembelajaran ekoteologi</p>
</div>
</body>
</html>

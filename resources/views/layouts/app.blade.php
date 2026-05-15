@php
    $themePref = auth()->check() ? (auth()->user()->theme ?? 'light') : 'light';
    $appName   = \App\Models\Setting::get('app.name', config('app.name', 'Eko-Scribe'));
@endphp
<!DOCTYPE html>
<html lang="id" class="{{ $themePref === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? '') ? $title.' • '.$appName : $appName }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        // Sinkron theme: localStorage > user pref > system. Mendengarkan event 'theme-changed'.
        (function() {
            const saved = localStorage.getItem('theme');
            const pref  = '{{ $themePref }}';
            const sys   = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const final = saved || pref || (sys ? 'dark' : 'light');
            const isDark = final === 'dark' || (final === 'system' && sys);
            document.documentElement.classList.toggle('dark', isDark);
            window.__themeApply = function(t) {
                const sysDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const dark = t === 'dark' || (t === 'system' && sysDark);
                document.documentElement.classList.toggle('dark', dark);
                localStorage.setItem('theme', t);
            };
        })();
    </script>
</head>
<body x-data="{ sidebarOpen: false }"
      @toggle-sidebar.window="sidebarOpen = !sidebarOpen"
      class="font-sans antialiased text-slate-700 dark:text-slate-200">
    <div class="app-bg flex">
        @include('layouts.sidebar')

        <div class="flex-1 lg:ml-72 min-h-screen flex flex-col">
            @include('layouts.topbar')

            @isset($header)
                <header class="px-4 sm:px-6 lg:px-10 pt-6">
                    <div class="glass px-5 py-4">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="flex-1 px-4 sm:px-6 lg:px-10 py-6">
                @if (session('success'))
                    <div class="glass border-emerald-200/60 bg-emerald-50/60 dark:bg-emerald-900/30 px-4 py-3 mb-5 text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
                        <x-icon name="check" class="w-5 h-5"/> <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="glass border-rose-200/60 bg-rose-50/60 dark:bg-rose-900/30 px-4 py-3 mb-5 text-rose-700 dark:text-rose-200 flex items-center gap-2">
                        <x-icon name="close" class="w-5 h-5"/> <span>{{ session('error') }}</span>
                    </div>
                @endif

                {{ $slot }}
            </main>

            <footer class="px-4 sm:px-6 lg:px-10 pb-6 text-center text-xs text-slate-500 dark:text-slate-400">
                © {{ now()->year }} {{ $appName }} v{{ config('app.version') }} • Platform pembelajaran
            </footer>
        </div>
    </div>
</body>
</html>

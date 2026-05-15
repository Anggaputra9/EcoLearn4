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
    <title>{{ ($title ?? 'Ruang Ujian') }} • {{ $appName }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function() {
            const saved = localStorage.getItem('theme');
            const pref  = '{{ $themePref }}';
            const sys   = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const final = saved || pref || (sys ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', final === 'dark' || (final === 'system' && sys));
        })();
    </script>
    <style>
        /* Hard-block navigasi keluar saat ujian berjalan */
        .exam-shell {
            min-height: 100vh;
            background:
                radial-gradient(60% 60% at 0% 0%, rgba(16, 185, 129, .10), transparent 60%),
                radial-gradient(50% 50% at 100% 100%, rgba(6, 182, 212, .10), transparent 60%),
                linear-gradient(180deg, #f0fdf4 0%, #ecfeff 100%);
        }
        html.dark .exam-shell {
            background:
                radial-gradient(60% 60% at 0% 0%, rgba(16, 185, 129, .15), transparent 60%),
                radial-gradient(50% 50% at 100% 100%, rgba(6, 182, 212, .12), transparent 60%),
                #020617;
        }
    </style>
</head>
<body class="font-sans antialiased text-slate-700 dark:text-slate-200">
<div class="exam-shell">
    {{ $slot }}
</div>

@if(session('success'))
    <div class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 glass border-emerald-200/60 bg-emerald-50/80 dark:bg-emerald-900/40 px-4 py-3 text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
        <span>{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 glass border-rose-200/60 bg-rose-50/80 dark:bg-rose-900/40 px-4 py-3 text-rose-700 dark:text-rose-200 flex items-center gap-2">
        <span>{{ session('error') }}</span>
    </div>
@endif
</body>
</html>

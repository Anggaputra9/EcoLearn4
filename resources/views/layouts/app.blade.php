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

    {{-- Konten kanan: digeser sebesar lebar sidebar di desktop (lg:ml-72).
         min-w-0 wajib supaya child flex (mis. tabel scroll) tidak memaksa overflow. --}}
    <div class="flex-1 lg:ml-72 min-h-screen flex flex-col min-w-0">
        @include('layouts.topbar')

        @isset($header)
            <header class="px-4 sm:px-6 lg:px-8 pt-4 sm:pt-6">
                <div class="glass px-4 sm:px-5 py-3 sm:py-4">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
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

        <footer class="px-4 sm:px-6 lg:px-8 pb-6 text-center text-xs text-slate-500 dark:text-slate-400">
            © {{ now()->year }} {{ $appName }} v{{ config('app.version') }} • Platform pembelajaran
        </footer>
    </div>
</div>

{{-- Modal global: konfirmasi keluar --}}
<x-confirm-modal
    name="sidebar-logout"
    title="Keluar dari Akun"
    icon="logout"
    tone="danger"
    confirm-text="Ya, Keluar"
    :action="route('logout')"
    method="POST"
    message="Anda akan keluar dari sesi ini. Pastikan semua pekerjaan sudah tersimpan."
/>

{{--
    Overlay loading global untuk proses AI.
    Dipakai dengan dua cara:

    1) Otomatis pada form submit:
       <form … data-ai-loading="Sedang menggenerate soal…">

    2) Programatik dari JS (modal AJAX, dsb.):
       window.aiLoader.show('AI sedang menulis materi…');
       window.aiLoader.hide();
--}}
<div id="ai-loader"
     class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-opacity"
     role="status" aria-live="polite" aria-hidden="true">
    <div class="glass-strong px-6 py-5 max-w-sm mx-4 text-center">
        <div class="mx-auto w-14 h-14 relative mb-3">
            {{-- Cincin luar berputar --}}
            <span class="absolute inset-0 rounded-full border-4 border-emerald-200/40 border-t-emerald-500 animate-spin"></span>
            {{-- Ikon sparkles di tengah --}}
            <span class="absolute inset-0 grid place-items-center">
                <svg viewBox="0 0 24 24" class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/>
                </svg>
            </span>
        </div>
        <p id="ai-loader-text" class="font-semibold text-slate-800 dark:text-slate-100">AI sedang bekerja…</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Mohon tunggu, jangan tutup halaman ini.</p>
    </div>
</div>

<script>
    (function () {
        const root = document.getElementById('ai-loader');
        const text = document.getElementById('ai-loader-text');

        const api = {
            show(message) {
                if (message && text) text.textContent = message;
                root.classList.remove('hidden');
                root.classList.add('flex');
                root.setAttribute('aria-hidden', 'false');
            },
            hide() {
                root.classList.add('hidden');
                root.classList.remove('flex');
                root.setAttribute('aria-hidden', 'true');
            },
        };
        window.aiLoader = api;

        // Auto-bind: form dengan atribut [data-ai-loading] akan menampilkan overlay
        // saat di-submit. Pesan diambil dari nilai atribut tersebut.
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.dataset.aiLoading === undefined) return;
            // Hindari double-submit & beri umpan balik visual
            const btn = form.querySelector('[type=submit]');
            if (btn) btn.setAttribute('disabled', 'disabled');
            api.show(form.dataset.aiLoading || 'AI sedang bekerja…');
        }, true);

        // Kalau navigasi balik via cache (BFCache), pastikan overlay tidak nyangkut.
        window.addEventListener('pageshow', () => api.hide());
    })();
</script>
</body>
</html>

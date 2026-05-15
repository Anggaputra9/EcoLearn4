@php
    $u = auth()->user();
    $appName = \App\Models\Setting::get('app.name', config('app.name', 'Eko-Scribe'));

    // Susun menu sesuai role.
    // Catatan: untuk admin, menu "Konfigurasi AI" sekarang gabungan
    // (provider/model default + API Key Pool). Begitu juga "Email & Notif"
    // (provider mail + Mail Key Pool).
    $items = [];
    if ($u->isAdmin()) {
        $items = [
            ['Beranda',          '/dashboard',         'home'],
            ['Kelola Pengguna',  '/admin/users',       'users'],
            ['Kelola Menu',      '/admin/menus',       'menu-list'],
            ['Pengaturan App',   '/admin/app',         'cog'],
            ['Konfigurasi AI',   '/admin/ai',          'sparkles'],
            ['Email & Notif',    '/admin/email',       'bell'],
            ['Changelog',        '/admin/changelogs',  'history'],
        ];
    } elseif ($u->isTeacher()) {
        $items = [
            ['Beranda',          '/dashboard',                 'home'],
            ['Materi Saya',      '/teacher',                   'book'],
            ['Kelas Saya',       '/teacher/classrooms',        'school'],
        ];
    } elseif ($u->isStudent()) {
        $items = [
            ['Beranda',          '/dashboard',          'home'],
            ['Daftar Materi',    '/student',            'book'],
            ['Kelas Saya',       '/student/classrooms', 'school'],
        ];
    }

    // Hitung index item yang aktif berdasarkan "longest-prefix-match",
    // sehingga /teacher/classrooms tidak ikut mengaktifkan /teacher.
    $currentPath = '/'.trim(request()->path(), '/');
    $activeIdx   = null;
    $activeLen   = -1;
    foreach ($items as $i => [$lbl, $url, $ic]) {
        $itemPath = '/'.trim($url, '/');
        if ($currentPath === $itemPath || str_starts_with($currentPath, $itemPath.'/')) {
            if (strlen($itemPath) > $activeLen) {
                $activeLen = strlen($itemPath);
                $activeIdx = $i;
            }
        }
    }
@endphp

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 z-40 w-[80vw] max-w-[18rem] lg:w-72 transition-transform duration-300">
    <div class="m-3 lg:m-4 h-[calc(100vh-1.5rem)] lg:h-[calc(100vh-2rem)] glass overflow-hidden flex flex-col">
        <a href="{{ route('dashboard') }}"
           @click="sidebarOpen = false"
           class="flex items-center gap-3 px-4 sm:px-5 py-4 sm:py-5 border-b border-white/40 dark:border-white/10">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 grid place-items-center shadow-lg shrink-0">
                <x-icon name="leaf" class="w-6 h-6 text-white"/>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-slate-800 dark:text-slate-100 tracking-tight truncate">{{ $appName }}</p>
                <p class="text-[10px] uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-semibold">v{{ config('app.version') }}</p>
            </div>
        </a>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            <p class="px-4 text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold mb-2">Navigasi</p>
            @foreach($items as $i => [$label, $url, $icon])
                <a href="{{ url($url) }}"
                   @click="sidebarOpen = false"
                   class="sidebar-link {{ $i === $activeIdx ? 'active' : '' }}">
                    <x-icon :name="$icon" class="w-5 h-5 shrink-0"/>
                    <span class="truncate">{{ $label }}</span>
                </a>
            @endforeach
        </nav>

        <div class="border-t border-white/40 dark:border-white/10 p-3">
            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/60 dark:hover:bg-white/10 transition">
                <img src="{{ $u->profile_photo_url }}" alt="{{ $u->name }}"
                     class="w-10 h-10 rounded-full ring-2 ring-emerald-200 dark:ring-emerald-700/40 object-cover">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $u->name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $u->email }}</p>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button class="sidebar-link w-full text-left text-rose-600 hover:bg-rose-50/60 hover:text-rose-700 dark:text-rose-400 dark:hover:bg-rose-900/30">
                    <x-icon name="logout" class="w-5 h-5"/> <span>Keluar</span>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- Backdrop mobile --}}
<div x-show="sidebarOpen" x-transition.opacity
     @click="sidebarOpen = false"
     class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-30 lg:hidden" style="display:none"></div>

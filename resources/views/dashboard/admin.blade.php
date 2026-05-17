<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-lg sm:text-xl font-bold text-slate-800 dark:text-slate-100">Dashboard Administrator</h2>
                <p class="text-xs sm:text-sm text-slate-500">Ringkasan aktivitas Eko-Scribe.</p>
            </div>
            <a href="{{ url('/admin/users') }}" class="btn-primary text-sm">
                <x-icon name="users" class="w-4 h-4"/> Kelola Pengguna
            </a>
        </div>
    </x-slot>

    {{-- Stat cards: setiap card adalah link ke halaman terkait --}}
    <div class="grid gap-4 sm:gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $cards = [
                ['label' => 'Total Pengguna', 'value' => $totalUsers,     'icon' => 'users',    'tone' => 'from-emerald-500 to-teal-600',     'href' => '/admin/users'],
                ['label' => 'Guru',           'value' => $totalTeachers,  'icon' => 'shield',   'tone' => 'from-violet-500 to-fuchsia-600',  'href' => '/admin/users?role_id=2'],
                ['label' => 'Siswa',          'value' => $totalStudents,  'icon' => 'users',    'tone' => 'from-sky-500 to-cyan-600',        'href' => '/admin/users?role_id=3'],
                ['label' => 'Materi',         'value' => $totalMaterials, 'icon' => 'book',     'tone' => 'from-amber-500 to-orange-600',    'href' => '/admin/menus'],
            ];
        @endphp
        @foreach($cards as $card)
            <a href="{{ url($card['href']) }}"
               class="glass p-5 flex items-start gap-4 hover:scale-[1.02] hover:shadow-lg transition group">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $card['tone'] }} grid place-items-center text-white shadow-lg shrink-0">
                    <x-icon :name="$card['icon']" class="w-6 h-6"/>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold">{{ $card['label'] }}</p>
                    <p class="text-2xl sm:text-3xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($card['value']) }}</p>
                </div>
                <x-icon name="arrow-right" class="w-4 h-4 text-slate-400 group-hover:text-emerald-600 transition mt-2"/>
            </a>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-4 sm:gap-6 mt-5 sm:mt-6">
        {{-- Aktivitas: hanya 2 metrik yang bermakna (pengumpulan & materi). --}}
        <div class="lg:col-span-2 glass p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Aktivitas Esai</h3>
                <span class="badge badge-emerald">Live</span>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <a href="{{ url('/admin/users') }}" class="text-center p-5 rounded-xl bg-white/60 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800/70 transition border border-white/60 dark:border-white/10">
                    <p class="text-3xl sm:text-4xl font-bold text-emerald-600">{{ number_format($totalSubmissions) }}</p>
                    <p class="text-xs text-slate-500 mt-1">Total Pengumpulan Esai</p>
                </a>
                <a href="{{ url('/admin/menus') }}" class="text-center p-5 rounded-xl bg-white/60 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800/70 transition border border-white/60 dark:border-white/10">
                    <p class="text-3xl sm:text-4xl font-bold text-violet-600">{{ number_format($totalMaterials) }}</p>
                    <p class="text-xs text-slate-500 mt-1">Materi Tersedia</p>
                </a>
            </div>

            {{-- Aksi cepat --}}
            <div class="mt-5 grid sm:grid-cols-3 gap-2">
                <a href="{{ url('/admin/ai') }}" class="btn-secondary text-sm justify-start">
                    <x-icon name="sparkles" class="w-4 h-4"/> Konfigurasi AI
                </a>
                <a href="{{ url('/admin/email') }}" class="btn-secondary text-sm justify-start">
                    <x-icon name="bell" class="w-4 h-4"/> Email & Notif
                </a>
                <a href="{{ url('/admin/changelogs') }}" class="btn-secondary text-sm justify-start">
                    <x-icon name="history" class="w-4 h-4"/> Changelog
                </a>
            </div>
        </div>

        <div class="glass p-5 sm:p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Rilis Terbaru</h3>
                <a href="{{ url('/admin/changelogs') }}" class="text-xs text-emerald-600 hover:underline">Lihat semua →</a>
            </div>
            @forelse($recentChangelog as $c)
                <div class="py-2 border-b border-white/40 dark:border-white/10 last:border-b-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="badge badge-emerald font-semibold">v{{ $c->version }}</span>
                        @if($c->released_at)
                            <span class="text-xs text-slate-500">{{ $c->released_at->isoFormat('D MMM Y') }}</span>
                        @endif
                        <span class="badge {{ ['major'=>'badge-rose','minor'=>'badge-sky','patch'=>'badge-violet','hotfix'=>'badge-amber'][$c->kind] ?? 'badge-emerald' }}">{{ ucfirst($c->kind) }}</span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Belum ada catatan rilis.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>

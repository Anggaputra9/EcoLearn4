<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800">Dashboard Administrator</h2>
        <p class="text-sm text-slate-500">Ringkasan aktivitas Eko-Scribe.</p>
    </x-slot>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Total Pengguna', 'value' => $totalUsers,       'icon' => 'users',     'tone' => 'from-emerald-500 to-teal-600'],
            ['label' => 'Guru',           'value' => $totalTeachers,    'icon' => 'shield',    'tone' => 'from-violet-500 to-fuchsia-600'],
            ['label' => 'Siswa',          'value' => $totalStudents,    'icon' => 'users',     'tone' => 'from-sky-500 to-cyan-600'],
            ['label' => 'Materi',         'value' => $totalMaterials,   'icon' => 'book',      'tone' => 'from-amber-500 to-orange-600'],
        ] as $card)
            <div class="glass p-5 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $card['tone'] }} grid place-items-center text-white shadow-lg">
                    <x-icon :name="$card['icon']" class="w-6 h-6"/>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold">{{ $card['label'] }}</p>
                    <p class="text-3xl font-bold text-slate-800">{{ $card['value'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mt-6">
        <div class="lg:col-span-2 glass p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-800">Aktivitas Esai</h3>
                <span class="badge badge-emerald">Live</span>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center p-4 rounded-xl bg-white/50">
                    <p class="text-3xl font-bold text-emerald-600">{{ $totalSubmissions }}</p>
                    <p class="text-xs text-slate-500 mt-1">Total Pengumpulan</p>
                </div>
                <div class="text-center p-4 rounded-xl bg-white/50">
                    <p class="text-3xl font-bold text-violet-600">{{ $totalMaterials }}</p>
                    <p class="text-xs text-slate-500 mt-1">Materi Tersedia</p>
                </div>
                <div class="text-center p-4 rounded-xl bg-white/50">
                    <p class="text-3xl font-bold text-sky-600">{{ $totalUsers }}</p>
                    <p class="text-xs text-slate-500 mt-1">Komunitas</p>
                </div>
            </div>
        </div>

        <div class="glass p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-slate-800">Rilis Terbaru</h3>
                <a href="{{ url('/admin/changelogs') }}" class="text-xs text-emerald-600 hover:underline">Lihat semua →</a>
            </div>
            @forelse($recentChangelog as $c)
                <div class="py-2 border-b border-white/40 last:border-b-0">
                    <div class="flex items-center gap-2">
                        <span class="badge badge-emerald font-semibold">v{{ $c->version }}</span>
                        <span class="text-xs text-slate-500">{{ $c->released_at->isoFormat('D MMM Y') }}</span>
                    </div>
                    <p class="text-sm text-slate-700 mt-1">{{ $c->title }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">Belum ada catatan rilis.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>

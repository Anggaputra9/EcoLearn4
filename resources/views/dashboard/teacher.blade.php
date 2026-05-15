<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Halo, {{ auth()->user()->name }}</h2>
                <p class="text-sm text-slate-500">Selamat datang kembali di ruang kelas digital Anda.</p>
            </div>
            <a href="{{ route('teacher.materials.create') }}" class="btn-primary">
                <x-icon name="sparkles" class="w-4 h-4"/> Buat Materi (AI)
            </a>
        </div>
    </x-slot>

    <div class="grid gap-5 sm:grid-cols-3 mb-6">
        @foreach([
            ['Materi Saya',     $totalMaterials,   'book',      'from-emerald-500 to-teal-600'],
            ['Soal Esai',       $totalQuestions,   'doc-text',  'from-violet-500 to-fuchsia-600'],
            ['Jawaban Siswa',   $totalSubmissions, 'chart',     'from-amber-500 to-orange-600'],
        ] as [$label, $value, $icon, $tone])
            <div class="glass p-5 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $tone }} grid place-items-center text-white shadow-lg">
                    <x-icon :name="$icon" class="w-6 h-6"/>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold">{{ $label }}</p>
                    <p class="text-3xl font-bold text-slate-800">{{ $value }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="glass p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800">Materi Terbaru</h3>
            <a href="{{ route('teacher.index') }}" class="text-xs text-emerald-600 hover:underline">Semua materi →</a>
        </div>
        @if($recentMaterials->isEmpty())
            <p class="text-sm text-slate-500">Belum ada materi. Coba <a href="{{ route('teacher.materials.create') }}" class="text-emerald-600 hover:underline">buat materi pertama</a>.</p>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($recentMaterials as $m)
                    <a href="{{ route('teacher.materials.show', $m) }}" class="block p-4 rounded-xl bg-white/50 hover:bg-white/80 transition border border-white/60">
                        <span class="badge badge-emerald">{{ $m->level }}</span>
                        <p class="mt-2 font-semibold text-slate-800">{{ $m->title }}</p>
                        <p class="text-xs text-slate-500">{{ $m->questions->count() }} soal · {{ $m->created_at->diffForHumans() }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Halo, {{ auth()->user()->name }}</h2>
                <p class="text-sm text-slate-500">Mari belajar ekoteologi hari ini.</p>
            </div>
            <a href="{{ route('student.index') }}" class="btn-primary w-full sm:w-auto flex justify-center items-center gap-2">
                <x-icon name="book" class="w-4 h-4"/> Lihat Materi
            </a>
        </div>
    </x-slot>

    <div class="grid gap-5 sm:grid-cols-3 mb-6">
        @foreach([
            ['Materi Tersedia', $availableMaterials, 'book', 'from-emerald-500 to-teal-600'],
            ['Esai Selesai',    $myAnswered,         'check', 'from-violet-500 to-fuchsia-600'],
            ['Skor Rata-rata',  $avgScore,           'chart', 'from-amber-500 to-orange-600'],
        ] as [$label, $value, $icon, $tone])
            <div class="glass p-5 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $tone }} grid place-items-center text-white shadow-lg shrink-0">
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
        <h3 class="font-semibold text-slate-800 mb-4">Hasil Esai Terbaru</h3>
        @if($recentSubmissions->isEmpty())
            <p class="text-sm text-slate-500">Belum ada jawaban. Yuk mulai dari <a href="{{ route('student.index') }}" class="text-emerald-600 hover:underline">daftar materi</a>.</p>
        @else
            <div class="space-y-3">
                @foreach($recentSubmissions as $s)
                    <a href="{{ route('student.submissions.show', $s) }}" class="block p-4 rounded-xl bg-white/50 hover:bg-white/80 transition border border-white/60">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-800 truncate">{{ $s->question->material->title }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ \Illuminate\Support\Str::limit($s->question->prompt_text, 90) }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                @if($s->status === 'graded')
                                    <p class="text-2xl font-bold text-emerald-600">{{ $s->score }}<span class="text-xs text-slate-400">/100</span></p>
                                @else
                                    <span class="badge badge-amber">{{ ucfirst($s->status) }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
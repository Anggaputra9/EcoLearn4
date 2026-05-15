<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 truncate">{{ $exam->title }}</h2>
                    <span class="badge {{ $exam->status === 'published' ? 'badge-emerald' : ($exam->status === 'closed' ? 'badge-rose' : 'badge-slate') }}">{{ ucfirst($exam->status) }}</span>
                </div>
                <p class="text-sm text-slate-500">Materi: {{ $exam->material->title }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button class="btn-secondary" @click="$dispatch('open-modal', 'exam-edit')"><x-icon name="pencil" class="w-4 h-4"/> Edit</button>
                @if($exam->status === 'draft')
                    <form method="POST" action="{{ route('teacher.exams.publish', $exam) }}" class="inline">
                        @csrf
                        <button class="btn-primary"><x-icon name="play" class="w-4 h-4"/> Mulai</button>
                    </form>
                @elseif($exam->status === 'published')
                    <form method="POST" action="{{ route('teacher.exams.close', $exam) }}" class="inline">
                        @csrf
                        <button class="btn-danger"><x-icon name="stop" class="w-4 h-4"/> Tutup</button>
                    </form>
                @endif
                <a href="{{ route('teacher.exams.report', $exam) }}" class="btn-secondary"><x-icon name="printer" class="w-4 h-4"/> Cetak Laporan</a>
                <form method="POST" action="{{ route('teacher.exams.release', $exam) }}" onsubmit="return confirm('Rilis hasil & kirim email ke peserta?')">
                    @csrf
                    <button class="btn-secondary"><x-icon name="send" class="w-4 h-4"/> Rilis Hasil</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="grid lg:grid-cols-4 gap-5 mb-6">
        @foreach([
            ['Total Peserta',   $stats['total'],       'users',  'from-emerald-500 to-teal-600'],
            ['Selesai',         $stats['submitted'],   'check',  'from-violet-500 to-fuchsia-600'],
            ['Sedang Ujian',    $stats['in_progress'], 'clock',  'from-amber-500 to-orange-600'],
            ['Skor Rata-rata',  $stats['avg_score'],   'chart',  'from-sky-500 to-cyan-600'],
        ] as [$lbl, $val, $icon, $tone])
            <div class="glass p-4 flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $tone }} grid place-items-center text-white">
                    <x-icon :name="$icon" class="w-5 h-5"/>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold">{{ $lbl }}</p>
                    <p class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ $val }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 glass overflow-hidden">
            <div class="px-5 py-3 border-b border-white/40 dark:border-white/10">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Daftar Peserta</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white/40 dark:bg-slate-800/40 text-xs uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        <tr>
                            <th class="px-5 py-3 text-left">Siswa</th>
                            <th class="px-5 py-3 text-left">Status</th>
                            <th class="px-5 py-3 text-left">Skor</th>
                            <th class="px-5 py-3 text-left">Pelanggaran</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/40 dark:divide-white/10">
                        @forelse($exam->attempts as $a)
                            <tr class="hover:bg-white/40 dark:hover:bg-white/5">
                                <td class="px-5 py-3 font-medium text-slate-800 dark:text-slate-100">{{ $a->user->name }}</td>
                                <td class="px-5 py-3">
                                    @php $cls = match($a->status){
                                        'submitted' => 'badge-emerald',
                                        'in_progress' => 'badge-amber',
                                        'disqualified' => 'badge-rose',
                                        default => 'badge-slate',
                                    }; @endphp
                                    <span class="badge {{ $cls }}">{{ str_replace('_',' ', $a->status) }}</span>
                                </td>
                                <td class="px-5 py-3 font-bold text-slate-800 dark:text-slate-100">{{ $a->total_score ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-500">
                                    Pindah tab: <span class="font-semibold">{{ $a->tab_switch_count }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('teacher.attempts.review', $a) }}" class="btn-ghost"><x-icon name="pencil" class="w-4 h-4"/></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Belum ada peserta.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="glass p-6">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-3">
                <x-icon name="trophy" class="w-5 h-5 text-amber-500"/> Top 10
            </h3>
            @if($stats['top']->isEmpty())
                <p class="text-sm text-slate-500">Belum ada nilai.</p>
            @else
                <ol class="space-y-2 text-sm">
                    @foreach($stats['top'] as $i => $a)
                        <li class="flex items-center gap-2">
                            <span class="w-6 h-6 grid place-items-center text-xs font-bold rounded-full
                                @if($i === 0) bg-amber-200 text-amber-800
                                @elseif($i === 1) bg-slate-200 text-slate-800
                                @elseif($i === 2) bg-orange-200 text-orange-800
                                @else bg-white/60 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300
                                @endif">{{ $i + 1 }}</span>
                            <span class="flex-1 truncate">{{ $a->user->name ?? '—' }}</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-300">{{ $a->total_score }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>

    <x-modal-glass name="exam-edit" title="Edit Ujian" max-width="3xl">
        <form id="exam-delete-form" method="POST" action="{{ route('teacher.exams.destroy', $exam) }}">
            @csrf @method('DELETE')
        </form>
        <form method="POST" action="{{ route('teacher.exams.update', $exam) }}" class="space-y-4">
            @csrf @method('PUT')
            @include('teacher.exams._fields', ['exam' => $exam])
            <div class="flex justify-between gap-2 pt-2">
                <button type="submit" form="exam-delete-form" class="btn-danger"
                        onclick="return confirm('Hapus ujian beserta semua attempt?')">
                    <x-icon name="trash" class="w-4 h-4"/> Hapus Ujian
                </button>
                <div class="flex gap-2">
                    <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'exam-edit')">Batal</button>
                    <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Simpan</button>
                </div>
            </div>
        </form>
    </x-modal-glass>
</x-app-layout>

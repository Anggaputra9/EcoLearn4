<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Koreksi Manual</h2>
            <p class="text-sm text-slate-500">{{ $attempt->user->name }} · {{ $attempt->exam->title }}</p>
        </div>
    </x-slot>

    <div class="space-y-5 max-w-4xl">
        <div class="glass p-5 grid sm:grid-cols-3 gap-4 text-sm">
            <div><p class="text-xs text-slate-500">Status</p><p class="font-semibold">{{ ucfirst(str_replace('_',' ',$attempt->status)) }}</p></div>
            <div><p class="text-xs text-slate-500">Pelanggaran tab</p><p class="font-semibold">{{ $attempt->tab_switch_count }}</p></div>
            <div><p class="text-xs text-slate-500">Skor total</p><p class="font-semibold">{{ $attempt->total_score ?? '—' }}/100</p></div>
        </div>

        @foreach($attempt->submissions as $sub)
            <div class="glass p-6">
                <div class="flex items-start gap-3 mb-3">
                    <div class="flex-1">
                        <p class="text-xs uppercase font-semibold text-emerald-700 dark:text-emerald-300">Soal</p>
                        <p class="mt-1 text-slate-800 dark:text-slate-100 leading-relaxed">{{ $sub->question?->prompt_text }}</p>
                        @if($sub->question?->rubric)
                            <p class="mt-2 text-xs text-slate-500"><span class="font-semibold">Rubrik:</span> {{ $sub->question->rubric }}</p>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl bg-white/50 dark:bg-slate-800/40 border border-white/60 dark:border-white/10 p-4">
                    <p class="text-xs text-slate-500 uppercase font-semibold mb-1">Jawaban Siswa</p>
                    <p class="whitespace-pre-wrap text-slate-700 dark:text-slate-200 leading-relaxed">{{ $sub->answer_text }}</p>
                </div>

                <form method="POST" action="{{ route('teacher.submissions.editFeedback', $sub) }}" class="mt-4 grid sm:grid-cols-[120px_1fr_auto] gap-3 items-start">
                    @csrf @method('PUT')
                    <input type="number" name="score" min="0" max="100" required value="{{ $sub->score }}"
                           class="input-glass" placeholder="0-100">
                    <textarea name="feedback" rows="3" required class="input-glass" placeholder="Tulis feedback…">{{ $sub->feedback }}</textarea>
                    <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Simpan</button>
                </form>

                <div class="mt-3 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        @if($sub->manually_graded) <span class="badge badge-violet">Dikoreksi guru</span> @endif
                        @if($sub->status === 'graded') <span class="badge badge-emerald">Selesai</span> @endif
                        @if($sub->graded_at) <span class="text-xs text-slate-400">· {{ $sub->graded_at->diffForHumans() }}</span> @endif
                    </div>
                    <form method="POST" action="{{ route('teacher.submissions.aiGrade', $sub) }}">
                        @csrf
                        <button class="btn-secondary text-sm"><x-icon name="sparkles" class="w-4 h-4"/> Jalankan AI</button>
                    </form>
                </div>
            </div>
        @endforeach

        <a href="{{ route('teacher.exams.show', $attempt->exam) }}" class="btn-secondary">
            <x-icon name="arrow-right" class="w-4 h-4 rotate-180"/> Kembali ke Ujian
        </a>
    </div>
</x-app-layout>

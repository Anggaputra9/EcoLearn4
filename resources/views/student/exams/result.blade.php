<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Hasil Ujian</h2>
        <p class="text-sm text-slate-500">{{ $exam->title }} · {{ $exam->material->title }}</p>
    </x-slot>

    @php
        $totalMax = $exam->material->questions->sum('max_score');
        $totalEarned = $attempt->submissions->sum(fn($s) => (int) ($s->score ?? 0));
        $answeredCount = $attempt->submissions->whereNotNull('score')->count();
        $totalQuestions = $exam->material->questions->count();
    @endphp

    <div class="grid lg:grid-cols-3 gap-6 max-w-5xl">
        <div class="lg:col-span-2 space-y-6">
            @if($attempt->status === 'disqualified')
                <div class="glass border-rose-300/60 bg-rose-50/60 dark:bg-rose-900/40 p-6 text-rose-700 dark:text-rose-200">
                    <div class="flex items-center gap-2 mb-2">
                        <x-icon name="shield" class="w-6 h-6"/>
                        <h3 class="font-bold text-lg">Anda Terdiskualifikasi</h3>
                    </div>
                    <p>Anda melanggar aturan ujian (pindah tab {{ $attempt->tab_switch_count }}× melebihi batas).</p>
                </div>
            @elseif($attempt->status === 'expired')
                <div class="glass border-amber-300/60 bg-amber-50/60 dark:bg-amber-900/40 p-6 text-amber-700 dark:text-amber-200">
                    <p>Waktu ujian telah berakhir.</p>
                </div>
            @endif

            @if($canSeeResult && $attempt->total_score !== null)
                <div class="glass-strong bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-6 border-emerald-300/40">
                    <p class="text-sm opacity-90">Skor Akhir (Akumulatif)</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-6xl font-extrabold">{{ $attempt->total_score }}</span>
                        <span class="text-xl opacity-80">/ 100</span>
                    </div>
                    <p class="mt-3 text-sm opacity-90">
                        Total poin: {{ $totalEarned }} / {{ $totalMax }} · Soal terjawab: {{ $answeredCount }} / {{ $totalQuestions }}
                    </p>
                    <p class="mt-1 text-xs opacity-80">Diserahkan {{ optional($attempt->submitted_at)->diffForHumans() }}</p>
                </div>
            @elseif(! $canSeeResult)
                <div class="glass border-amber-200/60 bg-amber-50/60 dark:bg-amber-900/40 p-6 text-amber-700 dark:text-amber-200">
                    <p>Hasil ujian belum dirilis oleh guru.</p>
                </div>
            @endif

            @if($exam->allow_review_answer && $canSeeResult)
                <div class="glass p-6">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3">Detail Jawaban</h3>
                    <div class="space-y-4">
                        @foreach($attempt->submissions as $sub)
                            @php $q = $sub->question; @endphp
                            <div class="rounded-xl bg-white/50 dark:bg-slate-800/40 border border-white/60 dark:border-white/10 p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="text-xs uppercase font-semibold text-emerald-700 dark:text-emerald-300">Soal</p>
                                    @if($q?->isMcq())
                                        <span class="badge badge-violet text-[10px]">Pilihan Ganda</span>
                                    @else
                                        <span class="badge badge-emerald text-[10px]">Esai</span>
                                    @endif
                                </div>
                                <p class="text-slate-800 dark:text-slate-100 leading-relaxed">{{ $q?->prompt_text }}</p>

                                @if($q?->isMcq())
                                    <ul class="mt-3 space-y-1.5">
                                        @foreach($q->normalizedOptions() as $opt)
                                            <li class="flex items-center gap-2 text-sm">
                                                <span class="w-6 h-6 grid place-items-center rounded-full text-xs font-bold shrink-0
                                                    @if($opt['key'] === $q->correct_option) bg-emerald-500 text-white
                                                    @elseif($opt['key'] === $sub->selected_option) bg-rose-500 text-white
                                                    @else bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300
                                                    @endif">{{ $opt['key'] }}</span>
                                                <span>{{ $opt['text'] }}</span>
                                                @if($opt['key'] === $q->correct_option)
                                                    <span class="badge badge-emerald text-[10px]">Kunci</span>
                                                @endif
                                                @if($opt['key'] === $sub->selected_option && $opt['key'] !== $q->correct_option)
                                                    <span class="badge badge-rose text-[10px]">Pilihan Anda</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="mt-3 text-xs uppercase font-semibold text-slate-500">Jawaban Anda</p>
                                    <p class="mt-1 text-slate-700 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">{{ $sub->answer_text }}</p>
                                @endif

                                @if($sub->status === 'graded')
                                    <div class="mt-3 flex items-center gap-2 flex-wrap">
                                        <span class="badge badge-emerald">Skor: {{ $sub->score }}/{{ $q?->max_score ?? 100 }}</span>
                                        @if($sub->manually_graded) <span class="badge badge-violet">Dikoreksi guru</span> @endif
                                    </div>
                                    @if($sub->feedback)
                                        <p class="mt-2 text-sm text-slate-700 dark:text-slate-200 italic">"{{ $sub->feedback }}"</p>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="glass p-6">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-2">Ringkasan</h3>
                <ul class="text-sm space-y-2">
                    <li class="flex justify-between"><span>Status</span><span class="font-semibold">{{ ucfirst(str_replace('_',' ',$attempt->status)) }}</span></li>
                    <li class="flex justify-between"><span>Pelanggaran tab</span><span class="font-semibold">{{ $attempt->tab_switch_count }}</span></li>
                    @if($attempt->started_at)
                        <li class="flex justify-between"><span>Mulai</span><span>{{ $attempt->started_at->format('d M Y H:i') }}</span></li>
                    @endif
                    @if($attempt->submitted_at)
                        <li class="flex justify-between"><span>Selesai</span><span>{{ $attempt->submitted_at->format('d M Y H:i') }}</span></li>
                    @endif
                </ul>
            </div>

            @if($exam->show_leaderboard && $leaderboard && $leaderboard->isNotEmpty())
                <div class="glass p-6">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-3">
                        <x-icon name="trophy" class="w-5 h-5 text-amber-500"/> Leaderboard
                    </h3>
                    <ol class="space-y-2 text-sm">
                        @foreach($leaderboard as $i => $a)
                            <li class="flex items-center gap-2 {{ $a->user_id === auth()->id() ? 'font-bold text-emerald-700 dark:text-emerald-300' : '' }}">
                                <span class="w-6 h-6 grid place-items-center text-xs font-bold rounded-full
                                    @if($i === 0) bg-amber-200 text-amber-800
                                    @elseif($i === 1) bg-slate-200 text-slate-800
                                    @elseif($i === 2) bg-orange-200 text-orange-800
                                    @else bg-white/60 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300
                                    @endif">{{ $i + 1 }}</span>
                                <span class="flex-1 truncate">{{ $a->user->name }}</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-300">{{ $a->total_score }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            <a href="{{ route('student.materials.show', $exam->material) }}" class="btn-secondary w-full justify-center">
                <x-icon name="arrow-right" class="w-4 h-4 rotate-180"/> Kembali ke Materi
            </a>
        </div>
    </div>
</x-app-layout>

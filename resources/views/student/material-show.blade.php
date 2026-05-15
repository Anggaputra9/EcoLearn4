<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 truncate">{{ $material->title }}</h2>
            <p class="text-sm text-slate-500">{{ $material->topic }} · Oleh {{ $material->teacher->name }}</p>
        </div>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="glass p-6">
                <div class="flex items-center gap-2 mb-3 flex-wrap">
                    <span class="badge badge-emerald">{{ $material->level }}</span>
                    @if($material->classroom)
                        <span class="badge badge-violet">{{ $material->classroom->name }}</span>
                    @endif
                </div>
                <article class="whitespace-pre-wrap text-slate-800 dark:text-slate-200 leading-relaxed">{{ $material->content }}</article>
            </div>

            <div class="glass p-6">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-4">Soal Esai (Latihan)</h3>
                @if($material->questions->isEmpty())
                    <p class="text-sm text-slate-500">Guru belum menambahkan soal latihan.</p>
                @else
                    <div class="space-y-3">
                        @foreach($material->questions as $i => $q)
                            @php $sub = $mySubmissions[$q->id] ?? null; @endphp
                            <div class="rounded-xl bg-white/50 dark:bg-slate-800/40 border border-white/60 dark:border-white/10 p-4 flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <p class="text-xs text-slate-500 mb-1">Soal {{ $i + 1 }}</p>
                                    <p class="text-slate-800 dark:text-slate-100 leading-relaxed">{{ $q->prompt_text }}</p>
                                    @if($sub && $sub->status === 'graded')
                                        <div class="mt-3 inline-flex items-center gap-2 text-sm">
                                            <span class="badge badge-emerald">Selesai</span>
                                            <span class="font-bold text-emerald-700 dark:text-emerald-300">Skor: {{ $sub->score }}/100</span>
                                        </div>
                                    @elseif($sub)
                                        <span class="badge badge-amber">{{ ucfirst($sub->status) }}</span>
                                    @endif
                                </div>
                                <div class="flex flex-col gap-2 shrink-0">
                                    <a href="{{ route('student.questions.answer', $q) }}" class="btn-primary text-sm py-1.5 px-3">{{ $sub ? 'Ulang' : 'Mulai' }}</a>
                                    @if($sub)
                                        <a href="{{ route('student.submissions.show', $sub) }}" class="btn-secondary text-sm py-1.5 px-3">Hasil</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Forum diskusi --}}
            @include('partials.discussions', ['material' => $material])
        </div>

        <div class="space-y-6">
            <div class="glass p-6">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3">Ujian</h3>
                @if($material->exams->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada ujian.</p>
                @else
                    <div class="space-y-2">
                        @foreach($material->exams as $exam)
                            <a href="{{ route('student.exams.lobby', $exam) }}" class="block p-3 rounded-xl bg-white/50 dark:bg-slate-800/40 hover:bg-white/80 dark:hover:bg-slate-800/70 border border-white/60 dark:border-white/10">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="badge {{ $exam->status === 'published' ? 'badge-emerald' : 'badge-rose' }}">{{ $exam->status === 'published' ? 'Live' : 'Tutup' }}</span>
                                    <span class="text-xs text-slate-500"><x-icon name="clock" class="w-3 h-3 inline"/> {{ $exam->duration_minutes }} mnt</span>
                                </div>
                                <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $exam->title }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

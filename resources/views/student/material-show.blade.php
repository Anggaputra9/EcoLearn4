<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    @if($material->meeting_number)
                        <span class="badge badge-amber">Pertemuan {{ $material->meeting_number }}</span>
                    @endif
                    <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 truncate">{{ $material->title }}</h2>
                </div>
                <p class="text-sm text-slate-500">{{ $material->topic }} · Oleh {{ $material->teacher->name }}</p>
            </div>

            <a href="{{ route('student.materials.pdf', $material) }}" class="btn-secondary">
                <x-icon name="printer" class="w-4 h-4"/> Unduh PDF
            </a>
        </div>
    </x-slot>

    @php
        $defaultTab = request('tab', 'materi');
        if (! in_array($defaultTab, ['materi', 'soal'], true)) $defaultTab = 'materi';
    @endphp

    <div x-data="{ tab: '{{ $defaultTab }}' }" class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">

            {{-- Tab switcher --}}
            <div class="glass p-1.5 inline-flex gap-1">
                <button type="button"
                        @click="tab = 'materi'"
                        :class="tab === 'materi' ? 'bg-emerald-500 text-white shadow' : 'text-slate-600 dark:text-slate-300 hover:bg-white/60 dark:hover:bg-white/10'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition flex items-center gap-2">
                    <x-icon name="book" class="w-4 h-4"/> Materi
                </button>
                <button type="button"
                        @click="tab = 'soal'"
                        :class="tab === 'soal' ? 'bg-emerald-500 text-white shadow' : 'text-slate-600 dark:text-slate-300 hover:bg-white/60 dark:hover:bg-white/10'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition flex items-center gap-2">
                    <x-icon name="pencil" class="w-4 h-4"/>
                    Soal Latihan
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold"
                          :class="tab === 'soal' ? 'bg-white/25 text-white' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'">
                        {{ $material->questions->count() }}
                    </span>
                </button>
            </div>

            {{-- TAB: MATERI --}}
            <div x-show="tab === 'materi'" x-cloak class="space-y-6">
                <div class="glass p-6">
                    <div class="flex items-center gap-2 mb-3 flex-wrap">
                        <span class="badge badge-emerald">{{ $material->level }}</span>
                        @if($material->classroom)
                            <span class="badge badge-violet">{{ $material->classroom->name }}</span>
                        @endif
                    </div>
                    <article class="whitespace-pre-wrap text-slate-800 dark:text-slate-200 leading-relaxed">{{ $material->content }}</article>
                </div>

                {{-- Forum diskusi tetap di tab materi --}}
                @include('partials.discussions', ['material' => $material])
            </div>

            {{-- TAB: SOAL --}}
            @php
                // Sembunyikan soal latihan kalau materi sudah punya ujian
                // yang dipublikasikan/ditutup. Tujuannya: siswa tidak bisa
                // mengintip bocoran soal sebelum/saat ujian berlangsung.
                $hasGradedExam = $material->exams->contains(fn ($e) => in_array($e->status, ['published', 'closed'], true));
            @endphp
            <div x-show="tab === 'soal'" x-cloak class="space-y-6">
                <div class="glass p-6">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-4">Soal Latihan</h3>
                    @if($hasGradedExam)
                        <div class="rounded-xl border border-amber-200/60 bg-amber-50/60 dark:bg-amber-900/30 p-4 text-amber-800 dark:text-amber-200 flex items-start gap-3">
                            <x-icon name="shield" class="w-5 h-5 shrink-0 mt-0.5"/>
                            <div class="text-sm leading-relaxed">
                                Soal-soal materi ini sedang/akan dipakai untuk ujian. Demi menjaga integritas, soal latihan disembunyikan.
                                Silakan kerjakan langsung melalui ujian di panel kanan.
                            </div>
                        </div>
                    @elseif($material->questions->isEmpty())
                        <p class="text-sm text-slate-500">Guru belum menambahkan soal latihan untuk materi ini.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($material->questions as $i => $q)
                                @php $sub = $mySubmissions[$q->id] ?? null; @endphp
                                <div class="rounded-xl bg-white/50 dark:bg-slate-800/40 border border-white/60 dark:border-white/10 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <p class="text-xs uppercase font-semibold text-slate-500">Soal {{ $i + 1 }}</p>
                                                @if($q->isMcq())
                                                    <span class="badge badge-violet text-[10px]">Pilihan Ganda</span>
                                                @else
                                                    <span class="badge badge-emerald text-[10px]">Esai</span>
                                                @endif
                                            </div>
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
                                        @if($q->isEssay())
                                            <div class="flex flex-col gap-2 shrink-0">
                                                <a href="{{ route('student.questions.answer', $q) }}" class="btn-primary text-sm py-1.5 px-3">{{ $sub ? 'Ulang' : 'Mulai' }}</a>
                                                @if($sub)
                                                    <a href="{{ route('student.submissions.show', $sub) }}" class="btn-secondary text-sm py-1.5 px-3">Hasil</a>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400 shrink-0">Latihan via ujian</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-4 text-xs text-slate-500">
                            <x-icon name="shield" class="w-3 h-3 inline -mt-0.5"/>
                            Untuk soal pilihan ganda, kerjakan melalui ujian agar penilaian akumulatif berjalan.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="glass p-6">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3">Ujian</h3>
                @if($material->exams->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada ujian.</p>
                @else
                    <div class="space-y-2">
                        @foreach($material->exams as $exam)
                            @if($exam->status === 'published' && $exam->isOpenNow())
                                <button type="button"
                                        @click="$dispatch('open-modal', 'exam-start-{{ $exam->id }}')"
                                        class="w-full text-left block p-3 rounded-xl bg-white/50 dark:bg-slate-800/40 hover:bg-white/80 dark:hover:bg-slate-800/70 border border-white/60 dark:border-white/10">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="badge badge-emerald">Live</span>
                                        <span class="text-xs text-slate-500"><x-icon name="clock" class="w-3 h-3 inline"/> {{ $exam->duration_minutes }} mnt</span>
                                    </div>
                                    <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $exam->title }}</p>
                                    <p class="text-xs text-emerald-600 mt-1">Klik untuk mulai →</p>
                                </button>
                                <x-confirm-modal
                                    name="exam-start-{{ $exam->id }}"
                                    title="Mulai Ujian Sekarang?"
                                    tone="primary"
                                    icon="play"
                                    confirm-text="Ya, Mulai Sekarang"
                                    :action="route('student.exams.start', $exam)"
                                    method="POST"
                                    :message="'Setelah mulai, Anda akan masuk ke ruang ujian. Aturan: dilarang pindah tab/copy-paste/klik kanan'.($exam->fullscreen_required ? ', wajib fullscreen' : '').'. Pelanggaran dapat menyebabkan diskualifikasi. Pastikan koneksi stabil dan waktu '.($exam->duration_minutes ?: '∞').' menit cukup.'" />
                            @else
                                <a href="{{ route('student.exams.lobby', $exam) }}" class="block p-3 rounded-xl bg-white/50 dark:bg-slate-800/40 hover:bg-white/80 dark:hover:bg-slate-800/70 border border-white/60 dark:border-white/10">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="badge {{ $exam->status === 'published' ? 'badge-amber' : 'badge-rose' }}">{{ $exam->status === 'published' ? 'Belum Mulai' : 'Tutup' }}</span>
                                        <span class="text-xs text-slate-500"><x-icon name="clock" class="w-3 h-3 inline"/> {{ $exam->duration_minutes }} mnt</span>
                                    </div>
                                    <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $exam->title }}</p>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

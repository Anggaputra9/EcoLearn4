<x-exam-layout>
    @php
        $appName = \App\Models\Setting::get('app.name', config('app.name', 'Eko-Scribe'));
        $totalQuestions = $questions->count();
    @endphp

    <div x-data="examRunner()" x-init="init()"
         x-on:beforeunload.window="handleUnload($event)"
         class="min-h-screen flex flex-col">

        {{-- Header ujian (sticky, glass) — TIDAK ada link kembali --}}
        <header class="sticky top-0 z-30 backdrop-blur-md bg-white/70 dark:bg-slate-950/60 border-b border-white/40 dark:border-white/10">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 grid place-items-center shadow-lg shrink-0">
                        <x-icon name="shield" class="w-5 h-5 text-white"/>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs uppercase tracking-wider text-emerald-700 dark:text-emerald-400 font-bold">Ruang Ujian · {{ $appName }}</p>
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate">{{ $exam->title }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 ml-auto">
                    <div class="glass px-3 py-2 flex items-center gap-2">
                        <x-icon name="clock" class="w-4 h-4 text-emerald-600"/>
                        <span x-text="formatTime(remaining)" class="font-mono font-bold text-slate-800 dark:text-slate-100">--:--</span>
                    </div>
                    @if($exam->prevent_tab_switch)
                        <div class="glass px-3 py-2 flex items-center gap-2">
                            <x-icon name="shield" class="w-4 h-4 text-rose-500"/>
                            <span class="text-[11px] text-slate-600 dark:text-slate-300 hidden sm:inline">Sisa pindah tab:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100" x-text="tabRemaining">{{ max(0, $exam->max_tab_switch - $attempt->tab_switch_count) }}</span>
                        </div>
                    @endif
                    <div class="glass px-3 py-2 flex items-center gap-2">
                        <span class="text-xs text-slate-500">Terjawab</span>
                        <span class="font-bold text-emerald-600" x-text="answeredCount">0</span>
                        <span class="text-xs text-slate-500">/ {{ $totalQuestions }}</span>
                    </div>
                </div>
            </div>
        </header>

        {{-- Banner peringatan --}}
        <div x-show="warning" x-transition x-cloak
             class="sticky top-[68px] z-20 bg-rose-500 text-white px-4 py-2 text-center text-sm font-medium shadow">
            <x-icon name="shield" class="w-4 h-4 inline -mt-0.5"/>
            <span x-text="warning"></span>
        </div>

        {{-- Body --}}
        <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-6">

            <form method="POST" action="{{ route('student.exams.submit', $exam) }}" id="exam-form" class="space-y-5"
                  @submit="onSubmit">
                @csrf

                @foreach($questions as $i => $q)
                    @php
                        $existingSub = $existing[$q->id] ?? null;
                        $existingText = optional($existingSub)->answer_text ?? '';
                        $existingChoice = optional($existingSub)->selected_option;
                    @endphp
                    <div class="glass p-6" data-question-row="{{ $q->id }}">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-xs uppercase font-bold tracking-wider text-emerald-700 dark:text-emerald-300">Soal {{ $i + 1 }} / {{ $totalQuestions }}</span>
                            @if($q->isMcq())
                                <span class="badge badge-violet text-[10px]">Pilihan Ganda</span>
                            @else
                                <span class="badge badge-emerald text-[10px]">Esai</span>
                            @endif
                        </div>
                        <p class="text-slate-800 dark:text-slate-100 leading-relaxed text-base">{{ $q->prompt_text }}</p>

                        @if($q->isMcq())
                            <div class="mt-4 space-y-2">
                                @foreach($q->normalizedOptions() as $opt)
                                    <label class="flex items-start gap-3 p-3 rounded-xl border border-white/60 dark:border-white/10 bg-white/40 dark:bg-slate-800/30 hover:bg-white/70 dark:hover:bg-slate-800/60 cursor-pointer transition has-[:checked]:bg-emerald-50 has-[:checked]:border-emerald-400 dark:has-[:checked]:bg-emerald-900/30">
                                        <input type="radio"
                                               name="choices[{{ $q->id }}]"
                                               value="{{ $opt['key'] }}"
                                               data-question-id="{{ $q->id }}"
                                               data-question-type="mcq"
                                               @checked($existingChoice === $opt['key'])
                                               @change="autosaveMcq($event)"
                                               class="mt-1 w-5 h-5 text-emerald-600">
                                        <span class="font-bold text-slate-700 dark:text-slate-200 w-6">{{ $opt['key'] }}.</span>
                                        <span class="text-slate-700 dark:text-slate-200 leading-relaxed flex-1">{{ $opt['text'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <textarea name="answers[{{ $q->id }}]" rows="7"
                                      data-question-id="{{ $q->id }}"
                                      data-question-type="essay"
                                      @input.debounce.1500ms="autosaveEssay($event.target)"
                                      class="input-glass mt-4 leading-relaxed"
                                      placeholder="Tulis jawaban Anda…">{{ $existingText }}</textarea>
                        @endif

                        <p class="mt-2 text-xs text-slate-400 h-4">
                            <span x-show="savingId === {{ $q->id }}">Menyimpan…</span>
                            <span x-show="savedId === {{ $q->id }}" class="text-emerald-600">✓ Tersimpan</span>
                        </p>
                    </div>
                @endforeach

                <div class="glass p-6 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-slate-500">
                        Skor akhir dihitung setelah semua soal dijawab. Pastikan tidak ada yang terlewat sebelum mengakhiri.
                    </p>
                    <button type="button" class="btn-primary" @click="$dispatch('open-modal', 'exam-finish')">
                        <x-icon name="check" class="w-4 h-4"/> Selesaikan Ujian
                    </button>
                </div>
            </form>
        </main>

        <footer class="text-center text-[11px] text-slate-500 dark:text-slate-400 py-4">
            <x-icon name="lock" class="w-3 h-3 inline -mt-0.5"/>
            Sesi ujian terkunci. Pindah tab, copy-paste, atau klik kanan dapat memicu peringatan/diskualifikasi.
        </footer>

        {{-- Modal konfirmasi selesai --}}
        <x-modal-glass name="exam-finish" title="Selesaikan Ujian?" max-width="md">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 grid place-items-center rounded-full bg-emerald-100 dark:bg-emerald-900/40 shrink-0">
                    <x-icon name="check" class="w-5 h-5 text-emerald-600"/>
                </div>
                <div>
                    <p class="text-slate-700 dark:text-slate-200 leading-relaxed">
                        Anda akan mengirimkan jawaban dan keluar dari ruang ujian. Skor dihitung dari akumulasi seluruh soal
                        (<span x-text="answeredCount"></span> dari {{ $totalQuestions }} terjawab).
                    </p>
                    <p class="mt-2 text-sm text-amber-600 dark:text-amber-400" x-show="answeredCount < {{ $totalQuestions }}" x-cloak>
                        Masih ada <span x-text="{{ $totalQuestions }} - answeredCount"></span> soal belum dijawab.
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'exam-finish')">Batal</button>
                <button type="button" class="btn-primary"
                        @click="$dispatch('close-modal', 'exam-finish'); submitForm();">
                    <x-icon name="check" class="w-4 h-4"/> Ya, Kirim & Selesai
                </button>
            </div>
        </x-modal-glass>
    </div>

    <script>
        function examRunner() {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            return {
                remaining: {{ (int) $remaining }},
                tabRemaining: {{ max(0, $exam->max_tab_switch - $attempt->tab_switch_count) }},
                savingId: null, savedId: null,
                warning: '',
                preventTab:   {{ $exam->prevent_tab_switch ? 'true' : 'false' }},
                preventCopy:  {{ $exam->prevent_copy_paste ? 'true' : 'false' }},
                preventRight: {{ $exam->prevent_right_click ? 'true' : 'false' }},
                fullscreen:   {{ $exam->fullscreen_required ? 'true' : 'false' }},
                duration: {{ (int) $exam->duration_minutes }},
                submitted: false,
                answeredCount: 0,

                init() {
                    document.body.classList.add('exam-locked');

                    // Hitung initial answered count
                    this.recomputeAnswered();

                    // Timer
                    if (this.duration > 0) {
                        this.tick = setInterval(() => {
                            this.remaining--;
                            if (this.remaining <= 0) {
                                clearInterval(this.tick);
                                this.warning = 'Waktu habis. Mengirim jawaban…';
                                this.submitForm();
                            }
                        }, 1000);
                    }

                    if (this.preventTab) {
                        document.addEventListener('visibilitychange', () => {
                            if (document.hidden) this.report('tab-switch');
                        });
                        window.addEventListener('blur', () => this.report('tab-switch'));
                    }
                    if (this.preventCopy) {
                        ['copy','cut','paste'].forEach(ev => {
                            document.addEventListener(ev, e => { e.preventDefault(); this.flash('Copy/paste dinonaktifkan saat ujian.'); });
                        });
                    }
                    if (this.preventRight) {
                        document.addEventListener('contextmenu', e => { e.preventDefault(); });
                    }
                    if (this.fullscreen && document.documentElement.requestFullscreen) {
                        try { document.documentElement.requestFullscreen(); } catch (e) {}
                        document.addEventListener('fullscreenchange', () => {
                            if (! document.fullscreenElement) this.flash('Mohon tetap dalam mode fullscreen.');
                        });
                    }

                    // Listener untuk update counter ketika user mengetik / memilih
                    document.querySelectorAll('[data-question-type="essay"]').forEach(el => {
                        el.addEventListener('input', () => this.recomputeAnswered());
                    });
                    document.querySelectorAll('[data-question-type="mcq"]').forEach(el => {
                        el.addEventListener('change', () => this.recomputeAnswered());
                    });
                },

                recomputeAnswered() {
                    const seen = new Set();
                    document.querySelectorAll('[data-question-type="essay"]').forEach(el => {
                        if ((el.value || '').trim() !== '') seen.add(el.dataset.questionId);
                    });
                    document.querySelectorAll('[data-question-type="mcq"]:checked').forEach(el => {
                        seen.add(el.dataset.questionId);
                    });
                    this.answeredCount = seen.size;
                },

                async report(event) {
                    try {
                        const res = await fetch('{{ route('student.exams.cheat', $exam) }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
                            body: JSON.stringify({ event }),
                        });
                        const data = await res.json();
                        this.tabRemaining = data.remaining;
                        if (data.disqualified) {
                            this.warning = 'Anda terdiskualifikasi karena melanggar aturan ujian.';
                            this.submitForm();
                        } else if (event === 'tab-switch') {
                            this.flash('Peringatan: Anda meninggalkan tab ujian. Sisa percobaan: ' + data.remaining);
                        }
                    } catch (e) {}
                },

                async autosaveEssay(textarea) {
                    const id = textarea.dataset.questionId;
                    this.savingId = +id; this.savedId = null;
                    try {
                        await fetch('{{ url('/student/exams/'.$exam->id.'/save') }}/' + id, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
                            body: JSON.stringify({ answer_text: textarea.value }),
                        });
                        this.savedId = +id;
                        setTimeout(() => { if (this.savedId === +id) this.savedId = null; }, 2000);
                    } catch (e) {}
                    finally { this.savingId = null; this.recomputeAnswered(); }
                },

                async autosaveMcq(e) {
                    const input = e.target;
                    const id = input.dataset.questionId;
                    this.savingId = +id; this.savedId = null;
                    try {
                        await fetch('{{ url('/student/exams/'.$exam->id.'/save') }}/' + id, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
                            body: JSON.stringify({ selected_option: input.value }),
                        });
                        this.savedId = +id;
                        setTimeout(() => { if (this.savedId === +id) this.savedId = null; }, 2000);
                    } catch (err) {}
                    finally { this.savingId = null; this.recomputeAnswered(); }
                },

                flash(msg) {
                    this.warning = msg;
                    setTimeout(() => { if (this.warning === msg) this.warning = ''; }, 3500);
                },

                submitForm() {
                    if (this.submitted) return;
                    this.submitted = true;
                    document.body.classList.remove('exam-locked');
                    document.getElementById('exam-form').submit();
                },

                onSubmit(e) { this.submitted = true; document.body.classList.remove('exam-locked'); },
                handleUnload(e) {
                    if (! this.submitted) { e.preventDefault(); e.returnValue = ''; }
                },

                formatTime(s) {
                    if (s < 0) s = 0;
                    const h = Math.floor(s / 3600);
                    const m = Math.floor((s % 3600) / 60);
                    const sec = s % 60;
                    if (h > 0) return [h, m, sec].map(n => String(n).padStart(2, '0')).join(':');
                    return [m, sec].map(n => String(n).padStart(2, '0')).join(':');
                },
            }
        }
    </script>
</x-exam-layout>

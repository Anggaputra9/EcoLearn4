<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ $exam->title }}</h2>
                <p class="text-sm text-slate-500">{{ $exam->material->title }}</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="glass px-4 py-2 flex items-center gap-2">
                    <x-icon name="clock" class="w-4 h-4 text-emerald-600"/>
                    <span x-text="formatTime(remaining)" class="font-mono font-bold text-slate-800 dark:text-slate-100">--:--</span>
                </div>
                @if($exam->prevent_tab_switch)
                    <div class="glass px-4 py-2 flex items-center gap-2">
                        <x-icon name="shield" class="w-4 h-4 text-rose-500"/>
                        <span class="text-xs text-slate-600 dark:text-slate-300">Sisa pindah tab:</span>
                        <span class="font-bold" x-text="tabRemaining">{{ max(0, $exam->max_tab_switch - $attempt->tab_switch_count) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <div x-data="examRunner()" x-init="init()"
         x-on:beforeunload.window="handleUnload($event)"
         class="max-w-4xl space-y-6">

        {{-- Banner status --}}
        <div x-show="warning" x-transition
             class="glass border-rose-300/60 bg-rose-50/60 dark:bg-rose-900/40 px-4 py-3 text-rose-700 dark:text-rose-200 flex items-center gap-2">
            <x-icon name="shield" class="w-5 h-5"/>
            <span x-text="warning"></span>
        </div>

        <form method="POST" action="{{ route('student.exams.submit', $exam) }}" id="exam-form" class="space-y-5"
              @submit="onSubmit">
            @csrf

            @foreach($questions as $i => $q)
                @php $existingText = optional($existing[$q->id] ?? null)->answer_text ?? ''; @endphp
                <div class="glass p-6">
                    <p class="text-xs uppercase font-semibold text-emerald-700 dark:text-emerald-300 tracking-wider">Soal {{ $i + 1 }}</p>
                    <p class="mt-1 text-slate-800 dark:text-slate-100 leading-relaxed">{{ $q->prompt_text }}</p>
                    <textarea name="answers[{{ $q->id }}]" rows="8"
                              data-question-id="{{ $q->id }}"
                              @input.debounce.1500ms="autosave($event.target)"
                              class="input-glass mt-3 leading-relaxed"
                              placeholder="Tulis jawaban Anda…">{{ $existingText }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">
                        <span x-show="savingId === {{ $q->id }}">Menyimpan…</span>
                        <span x-show="savedId === {{ $q->id }}" class="text-emerald-600">✓ Tersimpan</span>
                    </p>
                </div>
            @endforeach

            <div class="glass p-6 flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Pastikan semua soal sudah dijawab sebelum mengakhiri.</p>
                <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Selesaikan Ujian</button>
            </div>
        </form>
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

                init() {
                    document.body.classList.add('exam-locked');

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

                    // Anti tab switch
                    if (this.preventTab) {
                        document.addEventListener('visibilitychange', () => {
                            if (document.hidden) this.report('tab-switch');
                        });
                        window.addEventListener('blur', () => this.report('tab-switch'));
                    }

                    if (this.preventCopy) {
                        ['copy','cut','paste'].forEach(ev => {
                            document.addEventListener(ev, e => { e.preventDefault(); this.flash('Copy/paste dinonaktifkan.'); });
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

                async autosave(textarea) {
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
                    finally { this.savingId = null; }
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
                    const m = Math.floor(s / 60), sec = s % 60;
                    return String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
                },
            }
        }
    </script>
</x-app-layout>

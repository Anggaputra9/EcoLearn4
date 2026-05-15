<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 truncate">{{ $material->title }}</h2>
                    @if($material->classroom)
                        <span class="badge badge-violet">{{ $material->classroom->name }}</span>
                    @endif
                </div>
                <p class="text-sm text-slate-500">{{ $material->topic }}</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('teacher.materials.pdf', $material) }}" class="btn-secondary"><x-icon name="printer" class="w-4 h-4"/> Unduh PDF</a>
                <a href="{{ route('teacher.materials.edit', $material) }}" class="btn-secondary"><x-icon name="pencil" class="w-4 h-4"/> Edit</a>
                <button class="btn-danger" @click="$dispatch('open-modal', 'mat-del')"><x-icon name="trash" class="w-4 h-4"/> Hapus</button>
            </div>
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
                    Soal
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold"
                          :class="tab === 'soal' ? 'bg-white/25 text-white' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'">
                        {{ $material->questions->count() }}
                    </span>
                </button>
            </div>

            {{-- TAB: MATERI --}}
            <div x-show="tab === 'materi'" x-cloak class="space-y-6">
                <div class="glass p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="badge badge-emerald">{{ $material->level }}</span>
                        @unless($material->is_published) <span class="badge badge-amber">Draft</span> @endunless
                        <span class="text-xs text-slate-500">Diperbarui {{ $material->updated_at->diffForHumans() }}</span>
                    </div>
                    <article class="whitespace-pre-wrap text-slate-800 dark:text-slate-200 leading-relaxed">{{ $material->content }}</article>
                </div>

                {{-- Forum diskusi tetap dekat materi --}}
                @include('partials.discussions', ['material' => $material])
            </div>

            {{-- TAB: SOAL --}}
            <div x-show="tab === 'soal'" x-cloak class="space-y-6">
                <div class="glass p-6">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100">
                            Daftar Soal ({{ $material->questions->count() }})
                        </h3>
                        <div class="flex gap-2 flex-wrap">
                            <button class="btn-secondary text-sm py-1.5 px-3" @click="$dispatch('open-modal', 'q-create')">
                                <x-icon name="plus" class="w-4 h-4"/> Manual
                            </button>
                            <button class="btn-primary text-sm py-1.5 px-3" @click="$dispatch('open-modal', 'gen-q')">
                                <x-icon name="sparkles" class="w-4 h-4"/> Generate AI
                            </button>
                        </div>
                    </div>

                    @if($material->questions->isEmpty())
                        <p class="text-sm text-slate-500">Belum ada soal. Tambahkan secara manual atau generate dengan AI. Anda bisa mencampur soal esai dan pilihan ganda.</p>
                    @else
                        <ol class="space-y-3">
                            @foreach($material->questions as $i => $q)
                                <li class="rounded-xl bg-white/50 dark:bg-slate-800/40 border border-white/60 dark:border-white/10 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-xs uppercase font-semibold text-slate-500">Soal {{ $i + 1 }}</span>
                                                @if($q->isMcq())
                                                    <span class="badge badge-violet">Pilihan Ganda</span>
                                                @else
                                                    <span class="badge badge-emerald">Esai</span>
                                                @endif
                                                <span class="text-[11px] text-slate-400">Skor maks: {{ $q->max_score }}</span>
                                            </div>
                                            <p class="text-slate-800 dark:text-slate-100 leading-relaxed">{{ $q->prompt_text }}</p>

                                            @if($q->isMcq())
                                                <ul class="mt-3 space-y-1.5">
                                                    @foreach($q->normalizedOptions() as $opt)
                                                        <li class="flex items-start gap-2 text-sm">
                                                            <span class="w-6 h-6 grid place-items-center rounded-full text-xs font-bold shrink-0
                                                                {{ $opt['key'] === $q->correct_option ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                                                                {{ $opt['key'] }}
                                                            </span>
                                                            <span class="text-slate-700 dark:text-slate-200 leading-relaxed">{{ $opt['text'] }}</span>
                                                            @if($opt['key'] === $q->correct_option)
                                                                <span class="badge badge-emerald text-[10px]">Kunci</span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @elseif($q->rubric)
                                                <p class="mt-2 text-xs text-slate-500"><span class="font-semibold">Rubrik:</span> {{ $q->rubric }}</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <button class="btn-ghost p-2" @click="$dispatch('open-modal', 'q-edit-{{ $q->id }}')"><x-icon name="pencil" class="w-4 h-4"/></button>
                                            <button class="btn-ghost p-2 text-rose-600" @click="$dispatch('open-modal', 'q-del-{{ $q->id }}')"><x-icon name="trash" class="w-4 h-4"/></button>
                                        </div>
                                    </div>
                                </li>

                                {{-- Modal Edit Soal --}}
                                <x-modal-glass name="q-edit-{{ $q->id }}" title="Edit Soal {{ $q->isMcq() ? 'Pilihan Ganda' : 'Esai' }}" max-width="lg">
                                    <form method="POST" action="{{ route('teacher.questions.update', $q) }}" class="space-y-3"
                                          x-data="qEditor({{ $q->isMcq() ? 'true' : 'false' }}, @js($q->normalizedOptions()), @js($q->correct_option))">
                                        @csrf @method('PUT')
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Pertanyaan</label>
                                            <textarea name="prompt_text" required rows="3" class="input-glass">{{ $q->prompt_text }}</textarea>
                                        </div>

                                        @if($q->isMcq())
                                            <div>
                                                <label class="block text-sm font-medium mb-1">Pilihan Jawaban (centang yang benar)</label>
                                                <div class="space-y-2">
                                                    <template x-for="(o, i) in opts" :key="i">
                                                        <div class="flex items-center gap-2">
                                                            <input type="radio" name="correct_index" :value="i" x-model.number="correct" class="w-5 h-5 text-emerald-600">
                                                            <span class="font-bold w-6 text-slate-500" x-text="String.fromCharCode(65 + i)"></span>
                                                            <input type="text" :name="`options[${i}]`" x-model="o.text" class="input-glass flex-1" placeholder="Tulis opsi…">
                                                            <button type="button" class="btn-ghost p-1.5 text-rose-500" @click="opts.length > 2 && opts.splice(i, 1)" x-show="opts.length > 2"><x-icon name="close" class="w-4 h-4"/></button>
                                                        </div>
                                                    </template>
                                                </div>
                                                <button type="button" class="btn-ghost text-emerald-600 text-sm mt-2" @click="opts.length < 6 && opts.push({ text: '' })" x-show="opts.length < 6">
                                                    <x-icon name="plus" class="w-4 h-4"/> Tambah Opsi
                                                </button>
                                            </div>
                                        @else
                                            <div>
                                                <label class="block text-sm font-medium mb-1">Rubrik</label>
                                                <textarea name="rubric" rows="3" class="input-glass">{{ $q->rubric }}</textarea>
                                            </div>
                                        @endif

                                        <div>
                                            <label class="block text-sm font-medium mb-1">Skor Maksimum</label>
                                            <input type="number" name="max_score" min="1" max="100" value="{{ $q->max_score }}" class="input-glass w-32">
                                        </div>
                                        <div class="flex justify-end gap-2 pt-2">
                                            <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'q-edit-{{ $q->id }}')">Batal</button>
                                            <button class="btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </x-modal-glass>

                                <x-confirm-modal
                                    name="q-del-{{ $q->id }}"
                                    title="Hapus Soal"
                                    tone="danger"
                                    icon="trash"
                                    confirm-text="Hapus"
                                    :action="route('teacher.questions.destroy', $q)"
                                    method="DELETE"
                                    message="Hapus soal ini? Jawaban siswa terkait akan ikut hilang." />
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar kanan: Ujian --}}
        <div class="space-y-6">
            <div class="glass p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Ujian</h3>
                    <button class="btn-primary text-sm py-1.5 px-3" @click="$dispatch('open-modal', 'exam-create')">
                        <x-icon name="plus" class="w-4 h-4"/> Buat
                    </button>
                </div>

                @if($material->exams->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada ujian dari materi ini.</p>
                @else
                    <div class="space-y-2">
                        @foreach($material->exams as $exam)
                            <a href="{{ route('teacher.exams.show', $exam) }}" class="block p-3 rounded-xl bg-white/50 dark:bg-slate-800/40 hover:bg-white/80 dark:hover:bg-slate-800/70 border border-white/60 dark:border-white/10">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="badge {{ $exam->status === 'published' ? 'badge-emerald' : ($exam->status === 'closed' ? 'badge-rose' : 'badge-slate') }}">{{ ucfirst($exam->status) }}</span>
                                    <span class="text-xs text-slate-500"><x-icon name="clock" class="w-3 h-3 inline"/> {{ $exam->duration_minutes }} mnt</span>
                                </div>
                                <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $exam->title }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="glass p-6">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-2">Tautan Kelas</h3>
                <form method="POST" action="{{ route('teacher.materials.update', $material) }}" class="flex gap-2">
                    @csrf @method('PUT')
                    <input type="hidden" name="title" value="{{ $material->title }}">
                    <input type="hidden" name="topic" value="{{ $material->topic }}">
                    <input type="hidden" name="level" value="{{ $material->level }}">
                    <input type="hidden" name="content" value="{{ $material->content }}">
                    <input type="hidden" name="is_published" value="{{ $material->is_published ? 1 : 0 }}">
                    <select name="classroom_id" class="input-glass flex-1">
                        <option value="">— Tanpa kelas —</option>
                        @foreach($classrooms as $c)
                            <option value="{{ $c->id }}" @selected($material->classroom_id == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn-secondary"><x-icon name="check" class="w-4 h-4"/></button>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Generate Soal AI --}}
    <x-modal-glass name="gen-q" title="Generate Soal dengan AI" max-width="md">
        <form method="POST" action="{{ route('teacher.questions.generate', $material) }}" class="space-y-4"
              data-ai-loading="AI sedang menyusun soal sesuai materi…">
            @csrf
            <p class="text-sm text-slate-600 dark:text-slate-300">AI akan menyusun soal berdasarkan konten materi ini.</p>

            <div>
                <label class="block text-sm font-medium mb-2">Tipe Soal</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="kind" value="essay" class="peer sr-only">
                        <div class="text-center px-3 py-2 rounded-xl border border-white/60 dark:border-white/10 bg-white/40 dark:bg-slate-800/30 peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-500 transition text-sm font-medium">Esai</div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="kind" value="mcq" class="peer sr-only">
                        <div class="text-center px-3 py-2 rounded-xl border border-white/60 dark:border-white/10 bg-white/40 dark:bg-slate-800/30 peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-500 transition text-sm font-medium">Pilihan Ganda</div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="kind" value="mixed" class="peer sr-only" checked>
                        <div class="text-center px-3 py-2 rounded-xl border border-white/60 dark:border-white/10 bg-white/40 dark:bg-slate-800/30 peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-500 transition text-sm font-medium">Campuran</div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Jumlah Soal (1-15)</label>
                <input type="number" name="jumlah" min="1" max="15" value="5" required class="input-glass">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'gen-q')">Batal</button>
                <button class="btn-primary"><x-icon name="sparkles" class="w-4 h-4"/> Generate</button>
            </div>
        </form>
    </x-modal-glass>

    {{-- Modal Tambah Soal Manual --}}
    <x-modal-glass name="q-create" title="Tambah Soal Manual" max-width="lg">
        <form method="POST" action="{{ route('teacher.questions.store', $material) }}" class="space-y-3"
              x-data="qCreator()">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-2">Tipe Soal</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="essay" x-model="type" class="peer sr-only">
                        <div class="text-center px-3 py-2 rounded-xl border border-white/60 dark:border-white/10 bg-white/40 dark:bg-slate-800/30 peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-500 transition text-sm font-medium">Esai</div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="mcq" x-model="type" class="peer sr-only">
                        <div class="text-center px-3 py-2 rounded-xl border border-white/60 dark:border-white/10 bg-white/40 dark:bg-slate-800/30 peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-500 transition text-sm font-medium">Pilihan Ganda</div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Pertanyaan</label>
                <textarea name="prompt_text" required rows="3" class="input-glass" placeholder="Tulis pertanyaan…"></textarea>
            </div>

            {{-- Opsi MCQ --}}
            <div x-show="type === 'mcq'" x-cloak>
                <label class="block text-sm font-medium mb-2">Pilihan Jawaban (pilih yang benar)</label>
                <div class="space-y-2">
                    <template x-for="(o, i) in opts" :key="i">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="correct_index" :value="i" x-model.number="correct" class="w-5 h-5 text-emerald-600">
                            <span class="font-bold w-6 text-slate-500" x-text="String.fromCharCode(65 + i)"></span>
                            <input type="text" :name="`options[${i}]`" x-model="o.text" class="input-glass flex-1" placeholder="Tulis opsi…">
                            <button type="button" class="btn-ghost p-1.5 text-rose-500" @click="opts.length > 2 && opts.splice(i, 1)" x-show="opts.length > 2"><x-icon name="close" class="w-4 h-4"/></button>
                        </div>
                    </template>
                </div>
                <button type="button" class="btn-ghost text-emerald-600 text-sm mt-2" @click="opts.length < 6 && opts.push({ text: '' })" x-show="opts.length < 6">
                    <x-icon name="plus" class="w-4 h-4"/> Tambah Opsi
                </button>
            </div>

            {{-- Rubrik untuk esai --}}
            <div x-show="type === 'essay'" x-cloak>
                <label class="block text-sm font-medium mb-1">Rubrik (opsional)</label>
                <textarea name="rubric" rows="3" class="input-glass" placeholder="Kriteria penilaian"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Skor Maksimum</label>
                <input type="number" name="max_score" min="1" max="100" value="100" class="input-glass w-32">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'q-create')">Batal</button>
                <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Tambah</button>
            </div>
        </form>
    </x-modal-glass>

    {{-- Modal Buat Ujian --}}
    <x-modal-glass name="exam-create" title="Buat Ujian Baru" max-width="3xl">
        <form method="POST" action="{{ route('teacher.exams.store', $material) }}" class="space-y-4">
            @csrf
            @include('teacher.exams._fields', ['exam' => null])
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'exam-create')">Batal</button>
                <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Simpan sebagai Draft</button>
            </div>
        </form>
    </x-modal-glass>

    <x-confirm-modal
        name="mat-del"
        title="Hapus Materi"
        tone="danger"
        icon="trash"
        confirm-text="Ya, Hapus Permanen"
        :action="route('teacher.materials.destroy', $material)"
        method="DELETE"
        message="Hapus materi <strong>{{ e($material->title) }}</strong>? Semua soal, ujian, & jawaban siswa terkait juga akan ikut terhapus secara permanen." />

    <script>
        function qCreator() {
            return {
                type: 'essay',
                opts: [{ text: '' }, { text: '' }, { text: '' }, { text: '' }],
                correct: 0,
            };
        }
        function qEditor(isMcq, options, correctKey) {
            const opts = (options || []).map(o => ({ text: o.text }));
            const idx = (options || []).findIndex(o => o.key === correctKey);
            return {
                type: isMcq ? 'mcq' : 'essay',
                opts: opts.length ? opts : [{ text: '' }, { text: '' }],
                correct: idx >= 0 ? idx : 0,
            };
        }
    </script>
</x-app-layout>

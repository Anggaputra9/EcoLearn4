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
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.materials.edit', $material) }}" class="btn-secondary"><x-icon name="pencil" class="w-4 h-4"/> Edit</a>
                <button class="btn-danger" @click="$dispatch('open-modal', 'mat-del')"><x-icon name="trash" class="w-4 h-4"/> Hapus</button>
            </div>
        </div>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="glass p-6">
                <div class="flex items-center gap-2 mb-4">
                    <span class="badge badge-emerald">{{ $material->level }}</span>
                    @unless($material->is_published) <span class="badge badge-amber">Draft</span> @endunless
                    <span class="text-xs text-slate-500">Diperbarui {{ $material->updated_at->diffForHumans() }}</span>
                </div>
                <article class="whitespace-pre-wrap text-slate-800 dark:text-slate-200 leading-relaxed">{{ $material->content }}</article>
            </div>

            {{-- Soal --}}
            <div class="glass p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Soal Esai ({{ $material->questions->count() }})</h3>
                    <div class="flex gap-2">
                        <button class="btn-secondary text-sm py-1.5 px-3" @click="$dispatch('open-modal', 'q-create')">
                            <x-icon name="plus" class="w-4 h-4"/> Manual
                        </button>
                        <button class="btn-primary text-sm py-1.5 px-3" @click="$dispatch('open-modal', 'gen-q')">
                            <x-icon name="sparkles" class="w-4 h-4"/> Generate AI
                        </button>
                    </div>
                </div>

                @if($material->questions->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada soal. Tambahkan secara manual atau generate dengan AI.</p>
                @else
                    <ol class="space-y-3 list-decimal list-inside">
                        @foreach($material->questions as $q)
                            <li class="rounded-xl bg-white/50 dark:bg-slate-800/40 border border-white/60 dark:border-white/10 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1">
                                        <p class="text-slate-800 dark:text-slate-100 leading-relaxed">{{ $q->prompt_text }}</p>
                                        @if($q->rubric)
                                            <p class="mt-2 text-xs text-slate-500"><span class="font-semibold">Rubrik:</span> {{ $q->rubric }}</p>
                                        @endif
                                        <p class="mt-1 text-[11px] text-slate-400">Skor maks: {{ $q->max_score }}</p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button class="btn-ghost p-2" @click="$dispatch('open-modal', 'q-edit-{{ $q->id }}')"><x-icon name="pencil" class="w-4 h-4"/></button>
                                        <button class="btn-ghost p-2 text-rose-600" @click="$dispatch('open-modal', 'q-del-{{ $q->id }}')"><x-icon name="trash" class="w-4 h-4"/></button>
                                    </div>
                                </div>
                            </li>

                            <x-modal-glass name="q-edit-{{ $q->id }}" title="Edit Soal" max-width="lg">
                                <form method="POST" action="{{ route('teacher.questions.update', $q) }}" class="space-y-3">
                                    @csrf @method('PUT')
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Pertanyaan</label>
                                        <textarea name="prompt_text" required rows="4" class="input-glass">{{ $q->prompt_text }}</textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Rubrik</label>
                                        <textarea name="rubric" rows="3" class="input-glass">{{ $q->rubric }}</textarea>
                                    </div>
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

                            <x-modal-glass name="q-del-{{ $q->id }}" title="Hapus Soal" max-width="md">
                                <p class="text-slate-600 dark:text-slate-300">Hapus soal ini dari materi?</p>
                                <form method="POST" action="{{ route('teacher.questions.destroy', $q) }}" class="flex justify-end gap-2 mt-5">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'q-del-{{ $q->id }}')">Batal</button>
                                    <button class="btn-danger"><x-icon name="trash" class="w-4 h-4"/> Hapus</button>
                                </form>
                            </x-modal-glass>
                        @endforeach
                    </ol>
                @endif
            </div>

            {{-- Forum diskusi --}}
            @include('partials.discussions', ['material' => $material])
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
    <x-modal-glass name="gen-q" title="Generate Soal AI" max-width="md">
        <form method="POST" action="{{ route('teacher.questions.generate', $material) }}" class="space-y-4">
            @csrf
            <p class="text-sm text-slate-600 dark:text-slate-300">AI akan membuat soal esai dari materi ini.</p>
            <div>
                <label class="block text-sm font-medium mb-1">Jumlah Soal (1-10)</label>
                <input type="number" name="jumlah" min="1" max="10" value="3" required class="input-glass">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'gen-q')">Batal</button>
                <button class="btn-primary"><x-icon name="sparkles" class="w-4 h-4"/> Generate</button>
            </div>
        </form>
    </x-modal-glass>

    {{-- Modal Tambah Soal Manual --}}
    <x-modal-glass name="q-create" title="Tambah Soal Manual" max-width="lg">
        <form method="POST" action="{{ route('teacher.questions.store', $material) }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Pertanyaan</label>
                <textarea name="prompt_text" required rows="4" class="input-glass" placeholder="Tulis soal esai…"></textarea>
            </div>
            <div>
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

    <x-modal-glass name="mat-del" title="Hapus Materi" max-width="md">
        <p class="text-slate-600 dark:text-slate-300">Hapus materi <span class="font-semibold">{{ $material->title }}</span>? Semua soal, ujian, & jawaban siswa terkait juga akan terhapus.</p>
        <form method="POST" action="{{ route('teacher.materials.destroy', $material) }}" class="flex justify-end gap-2 mt-5">
            @csrf @method('DELETE')
            <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'mat-del')">Batal</button>
            <button class="btn-danger"><x-icon name="trash" class="w-4 h-4"/> Hapus Permanen</button>
        </form>
    </x-modal-glass>
</x-app-layout>

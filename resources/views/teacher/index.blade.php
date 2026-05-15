<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Materi Saya</h2>
                <p class="text-sm text-slate-500">Materi yang telah Anda buat untuk siswa.</p>
            </div>
            <button class="btn-primary" @click="$dispatch('open-modal', 'mat-create')">
                <x-icon name="sparkles" class="w-4 h-4"/> Buat Materi
            </button>
        </div>
    </x-slot>

    <form method="GET" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-[1fr_180px_180px_auto]">
        <div class="relative">
            <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
            <input name="q" value="{{ $q }}" placeholder="Cari judul atau topik…" class="input-glass pl-9">
        </div>
        <select name="level" class="input-glass">
            <option value="">Semua tingkat</option>
            @foreach(['SD','SMP','SMA','Umum'] as $lv)
                <option value="{{ $lv }}" @selected($level === $lv)>{{ $lv }}</option>
            @endforeach
        </select>
        <select name="classroom_id" class="input-glass">
            <option value="">Semua kelas</option>
            @foreach($classrooms as $c)
                <option value="{{ $c->id }}" @selected((string)$classroomId === (string)$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <button class="btn-secondary"><x-icon name="search" class="w-4 h-4"/> Filter</button>
    </form>

    @if($materials->isEmpty())
        <div class="glass p-10 text-center text-slate-500">
            Belum ada materi yang cocok. <a href="#" @click.prevent="$dispatch('open-modal', 'mat-create')" class="text-emerald-600 font-medium hover:underline">Buat materi pertama →</a>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($materials as $m)
                <div class="glass p-5 flex flex-col">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="badge badge-emerald">{{ $m->level }}</span>
                        @if($m->classroom)
                            <span class="badge badge-violet">{{ $m->classroom->name }}</span>
                        @endif
                        @unless($m->is_published)
                            <span class="badge badge-amber">Draft</span>
                        @endunless
                    </div>
                    <h3 class="mt-3 font-semibold text-slate-800 dark:text-slate-100">{{ $m->title }}</h3>
                    <p class="text-sm text-slate-500 mt-1">{{ $m->topic }}</p>
                    <p class="text-xs text-slate-400 mt-3">{{ $m->questions->count() }} soal · {{ $m->created_at->diffForHumans() }}</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('teacher.materials.show', $m) }}" class="btn-primary text-sm py-1.5 px-3">Buka</a>
                        <a href="{{ route('teacher.submissions', $m) }}" class="btn-secondary text-sm py-1.5 px-3">Hasil Siswa</a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $materials->links() }}</div>
    @endif

    {{-- Modal: Buat Materi (AI generate + simpan dalam satu modal) --}}
    <x-modal-glass name="mat-create" title="Buat Materi Baru" max-width="3xl">
        <div x-data="materialCreator()" class="space-y-4">
            <div class="grid gap-3 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Judul Materi</label>
                    <input x-model="form.title" required class="input-glass" placeholder="Misal: Etika Kepedulian terhadap Bumi">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Topik Ekoteologi</label>
                    <input x-model="form.topic" required class="input-glass" placeholder="Stewardship terhadap ciptaan">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Tingkat</label>
                    <select x-model="form.level" class="input-glass">
                        <option value="SD">SD</option><option value="SMP">SMP</option>
                        <option value="SMA">SMA</option><option value="Umum">Umum</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Tautkan ke Kelas (opsional)</label>
                    <select x-model="form.classroom_id" class="input-glass">
                        <option value="">— Tanpa kelas (publik) —</option>
                        @foreach($classrooms as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" class="btn-secondary" :disabled="loading" @click="generate">
                    <x-icon name="sparkles" class="w-4 h-4"/>
                    <span x-text="loading ? 'Memuat AI…' : 'Generate dengan AI'"></span>
                </button>
                <span x-show="error" class="text-sm text-rose-600" x-text="error"></span>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Konten Materi (boleh edit)</label>
                <textarea x-model="form.content" rows="14" required class="input-glass font-mono text-sm leading-relaxed" placeholder="Isi materi akan muncul di sini setelah generate, atau bisa diketik manual."></textarea>
            </div>

            <form method="POST" action="{{ route('teacher.materials.store') }}" class="flex justify-end gap-2 pt-2">
                @csrf
                <input type="hidden" name="title" :value="form.title">
                <input type="hidden" name="topic" :value="form.topic">
                <input type="hidden" name="level" :value="form.level">
                <input type="hidden" name="content" :value="form.content">
                <input type="hidden" name="classroom_id" :value="form.classroom_id">
                <input type="hidden" name="is_published" value="1">

                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'mat-create')">Batal</button>
                <button class="btn-primary" :disabled="!form.title || !form.topic || !form.content">
                    <x-icon name="check" class="w-4 h-4"/> Simpan Materi
                </button>
            </form>
        </div>
    </x-modal-glass>

    <script>
        function materialCreator() {
            return {
                loading: false, error: '',
                form: { title: '', topic: '', level: 'SMA', classroom_id: '', content: '' },
                async generate() {
                    if (!this.form.title || !this.form.topic) { this.error = 'Isi judul & topik dulu.'; return; }
                    this.error = ''; this.loading = true;
                    try {
                        const res = await fetch('{{ route('teacher.materials.generate.ajax') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json',
                                       'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                       'Accept': 'application/json' },
                            body: JSON.stringify(this.form),
                        });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.message || 'AI gagal.');
                        this.form.content = data.content;
                    } catch (e) {
                        this.error = e.message;
                    } finally { this.loading = false; }
                },
            }
        }
    </script>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Materi Saya</h2>
                <p class="text-sm text-slate-500">Materi yang telah Anda buat untuk siswa.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('teacher.materials.history') }}" class="btn-secondary">
                    <x-icon name="clock" class="w-4 h-4"/> Histori
                    @if(($trashedCount ?? 0) > 0)
                        <span class="ml-1 px-1.5 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 text-[10px] font-semibold">{{ $trashedCount }}</span>
                    @endif
                </a>
                <button class="btn-primary" @click="$dispatch('open-modal', 'mat-create')">
                    <x-icon name="sparkles" class="w-4 h-4"/> Buat Materi
                </button>
            </div>
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
                        @if($m->meeting_number)
                            <span class="badge badge-amber">Pertemuan {{ $m->meeting_number }}</span>
                        @endif
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
                    @php $bundle = $m->outputBundle(); @endphp
                    @if(count($bundle) > 1)
                        <div class="mt-2 flex items-center gap-1 flex-wrap">
                            @foreach($bundle as $out)
                                <span class="inline-flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide
                                            text-emerald-700 dark:text-emerald-300 bg-emerald-50/70 dark:bg-emerald-900/30
                                            border border-emerald-200/70 dark:border-emerald-800/40 rounded-md px-1.5 py-0.5">
                                    <x-icon :name="$out['icon']" class="w-3 h-3"/>{{ $out['label'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif
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

    {{-- Modal: Buat Materi (multi-format AI generator a la NotebookLM) --}}
    <x-modal-glass name="mat-create" title="Buat Materi Baru" max-width="4xl">
        @php $formatsMeta = \App\Models\Material::formats(); @endphp
        <div x-data="materialCreator({{ (int)($nextMeeting ?? 1) }}, @js($formatsMeta))" class="space-y-5">

            {{-- Step indicator --}}
            <div class="flex items-center gap-2 text-xs">
                <span :class="step === 1 ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500'"
                      class="px-2 py-1 rounded-full font-semibold">1. Briefing</span>
                <span class="text-slate-300">→</span>
                <span :class="step === 2 ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500'"
                      class="px-2 py-1 rounded-full font-semibold">2. Pratinjau & Edit</span>
                <span class="text-slate-300">→</span>
                <span class="px-2 py-1 rounded-full font-semibold bg-slate-200 dark:bg-slate-700 text-slate-500">3. Simpan</span>
            </div>

            {{-- ============ STEP 1: BRIEFING ============ --}}
            <div x-show="step === 1" class="space-y-4">
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Pertemuan ke- <span class="text-xs font-normal text-slate-400">(otomatis)</span>
                        </label>
                        <div class="flex items-center gap-1">
                            <input x-model.number="form.meeting_number" type="number" min="1" max="9999" class="input-glass" :placeholder="`Auto: ${suggestedMeeting}`">
                            <button type="button" class="btn-ghost p-2" title="Pakai nomor otomatis"
                                    @click="form.meeting_number = suggestedMeeting">
                                <x-icon name="sparkles" class="w-4 h-4"/>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1" x-show="suggestedMeeting">
                            Saran berikutnya: <span class="font-semibold" x-text="suggestedMeeting"></span>
                        </p>
                    </div>
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
                    <div>
                        <label class="block text-sm font-medium mb-1">Kelas (opsional)</label>
                        <select x-model="form.classroom_id" class="input-glass" @change="refreshSuggestion()">
                            <option value="">— Tanpa kelas (publik) —</option>
                            @foreach($classrooms as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Format pilihan multi --}}
                <div>
                    <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                        <label class="block text-sm font-medium">
                            Format Hasil
                            <span class="text-xs font-normal text-slate-400">(pilih ≥ 1, AI akan menyusun masing-masing)</span>
                        </label>
                        <div class="flex items-center gap-1 text-xs">
                            <button type="button" class="text-emerald-600 hover:underline" @click="selectAllFormats">Pilih semua</button>
                            <span class="text-slate-300">·</span>
                            <button type="button" class="text-slate-500 hover:underline" @click="clearFormats">Kosongkan</button>
                        </div>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($formatsMeta as $key => $meta)
                            <label class="cursor-pointer">
                                <input type="checkbox" value="{{ $key }}" x-model="form.formats" class="peer sr-only">
                                <div class="h-full flex items-start gap-3 p-3 rounded-xl border border-white/60 dark:border-white/10
                                            bg-white/40 dark:bg-slate-800/30 hover:bg-white/70 dark:hover:bg-slate-800/60
                                            peer-checked:border-emerald-500 peer-checked:bg-emerald-50/70
                                            dark:peer-checked:bg-emerald-900/30 transition">
                                    <div class="w-9 h-9 grid place-items-center rounded-lg bg-emerald-100/70 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 shrink-0">
                                        <x-icon :name="$meta['icon']" class="w-5 h-5"/>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $meta['label'] }}</div>
                                        <p class="text-[11px] text-slate-500 leading-snug mt-0.5">{{ $meta['hint'] }}</p>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Custom prompt --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium">
                            Arahan Khusus (opsional)
                            <span class="text-xs font-normal text-slate-400">— biar materi spesifik, bukan cuma topik luas</span>
                        </label>
                        <button type="button" class="text-xs text-emerald-600 hover:underline" @click="showPresets = !showPresets">
                            <x-icon name="sparkles" class="w-3 h-3 inline -mt-0.5"/> Saran prompt
                        </button>
                    </div>
                    <textarea x-model="form.custom_prompt" rows="3" maxlength="2000" class="input-glass text-sm"
                              placeholder="Contoh: fokus pada perspektif Kristen Protestan, sertakan ayat Kejadian 1:28 & 2:15, kaitkan dengan kasus deforestasi di Kalimantan, gunakan analogi yang dekat dengan siswa SMA."></textarea>
                    <div x-show="showPresets" x-cloak class="mt-2 grid gap-1.5 sm:grid-cols-2 text-[11px]">
                        <template x-for="p in presets" :key="p">
                            <button type="button"
                                    class="text-left p-2 rounded-lg bg-slate-100/60 dark:bg-slate-800/40 hover:bg-emerald-50/70 dark:hover:bg-emerald-900/20"
                                    @click="form.custom_prompt = p">
                                <span x-text="p"></span>
                            </button>
                        </template>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">
                        <span x-text="form.custom_prompt.length"></span>/2000
                    </p>
                </div>

                <div class="flex items-center justify-between gap-2 pt-2 border-t border-white/40 dark:border-white/10">
                    <span x-show="error" class="text-sm text-rose-600 truncate" x-text="error"></span>
                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'mat-create')">Batal</button>
                        <button type="button" class="btn-primary" :disabled="loading || !canGenerate" @click="generate">
                            <x-icon name="sparkles" class="w-4 h-4"/>
                            <span x-text="loading ? 'AI sedang menyusun…' : `Generate (${form.formats.length} format)`"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============ STEP 2: PREVIEW & EDIT ============ --}}
            <div x-show="step === 2" x-cloak class="space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <p class="text-sm text-slate-500">
                        AI menghasilkan <span class="font-semibold text-emerald-600" x-text="outputs.length"></span>
                        format. Anda bisa mengedit tiap tab sebelum disimpan.
                    </p>
                    <button type="button" class="btn-ghost text-sm" @click="step = 1">
                        <x-icon name="arrow-left" class="w-4 h-4"/> Ubah Briefing
                    </button>
                </div>

                <div x-show="partial.length > 0" x-cloak class="rounded-xl border border-amber-300/60 bg-amber-50/60 dark:bg-amber-900/30 px-3 py-2 text-amber-800 dark:text-amber-200 text-xs">
                    <div class="font-semibold">Sebagian format gagal:</div>
                    <ul class="list-disc list-inside">
                        <template x-for="msg in partial" :key="msg"><li x-text="msg"></li></template>
                    </ul>
                </div>

                {{-- Tabs --}}
                <div class="flex gap-1 flex-wrap border-b border-white/50 dark:border-white/10 pb-2">
                    <template x-for="(out, i) in outputs" :key="out.format">
                        <button type="button"
                                @click="activeTab = i"
                                :class="activeTab === i
                                    ? 'bg-emerald-500 text-white shadow'
                                    : 'bg-white/40 dark:bg-slate-800/40 text-slate-600 dark:text-slate-300 hover:bg-white/70'"
                                class="px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5 transition">
                            <span x-text="out.label"></span>
                            <span class="text-[10px] opacity-70" x-show="i === 0">utama</span>
                        </button>
                    </template>
                </div>

                {{-- Editor untuk tab aktif --}}
                <template x-for="(out, i) in outputs" :key="out.format">
                    <div x-show="activeTab === i" class="space-y-2">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <span class="badge badge-emerald" x-text="out.label"></span>
                                <button type="button" class="btn-ghost text-xs"
                                        @click="setPrimary(i)" x-show="i !== 0">
                                    Jadikan utama
                                </button>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" class="btn-ghost text-xs" @click="copyOutput(i)">
                                    <x-icon name="doc-text" class="w-3.5 h-3.5"/> Salin
                                </button>
                                <button type="button" class="btn-ghost text-xs" @click="regenerate(i)" :disabled="loading">
                                    <x-icon name="refresh" class="w-3.5 h-3.5"/> Generate ulang
                                </button>
                                <button type="button" class="btn-ghost text-xs text-rose-600" @click="removeOutput(i)" x-show="outputs.length > 1">
                                    <x-icon name="trash" class="w-3.5 h-3.5"/> Hapus
                                </button>
                            </div>
                        </div>
                        <textarea x-model="out.content" rows="14" class="input-glass font-mono text-sm leading-relaxed"></textarea>
                    </div>
                </template>

                {{-- Form submit final --}}
                <form method="POST" action="{{ route('teacher.materials.store') }}" class="flex justify-end gap-2 pt-2 border-t border-white/40 dark:border-white/10">
                    @csrf
                    <input type="hidden" name="title" :value="form.title">
                    <input type="hidden" name="topic" :value="form.topic">
                    <input type="hidden" name="level" :value="form.level">
                    <input type="hidden" name="classroom_id" :value="form.classroom_id">
                    <input type="hidden" name="meeting_number" :value="form.meeting_number || ''">
                    <input type="hidden" name="custom_prompt" :value="form.custom_prompt">
                    <input type="hidden" name="format" :value="outputs[0]?.format || 'standard'">
                    <input type="hidden" name="content" :value="outputs[0]?.content || ''">
                    <template x-for="(out, i) in outputs" :key="'h-'+out.format">
                        <span>
                            <input type="hidden" :name="`outputs[${i}][format]`" :value="out.format">
                            <input type="hidden" :name="`outputs[${i}][label]`"  :value="out.label">
                            <input type="hidden" :name="`outputs[${i}][content]`" :value="out.content">
                        </span>
                    </template>
                    <input type="hidden" name="is_published" value="1">

                    <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'mat-create')">Batal</button>
                    <button class="btn-primary" :disabled="!hasContent">
                        <x-icon name="check" class="w-4 h-4"/> Simpan Materi
                    </button>
                </form>
            </div>
        </div>
    </x-modal-glass>

    <script>
        function materialCreator(initialNext, formatsMeta) {
            return {
                step: 1,
                loading: false,
                error: '',
                showPresets: false,
                suggestedMeeting: initialNext || 1,
                formatsMeta: formatsMeta || {},
                form: {
                    title: '', topic: '', level: 'SMA',
                    classroom_id: '',
                    meeting_number: initialNext || 1,
                    custom_prompt: '',
                    formats: ['standard'],
                },
                outputs: [],   // [{format,label,content}]
                partial: [],
                activeTab: 0,
                presets: [
                    'Tekankan pendekatan dialog antar-iman dan beri contoh dari konteks Indonesia.',
                    'Sertakan minimal 1 ayat Alkitab (Kejadian 1:28; 2:15) dan refleksinya untuk siswa.',
                    'Gunakan studi kasus deforestasi di Kalimantan/Papua dengan data terkini.',
                    'Sasaran SMA kelas X dengan bahasa santai dan analogi keseharian.',
                    'Fokus pada aksi praktis yang bisa siswa lakukan di sekolah dan rumah.',
                    'Sajikan perspektif lintas agama (Kristen, Islam, Hindu, Buddha) secara seimbang.',
                ],

                get canGenerate() {
                    return this.form.title.trim() !== ''
                        && this.form.topic.trim() !== ''
                        && this.form.formats.length > 0;
                },
                get hasContent() {
                    return this.outputs.length > 0 && this.outputs[0].content.trim() !== '';
                },

                selectAllFormats() {
                    this.form.formats = Object.keys(this.formatsMeta);
                },
                clearFormats() {
                    this.form.formats = [];
                },

                async refreshSuggestion() {
                    try {
                        const params = new URLSearchParams();
                        if (this.form.classroom_id) params.set('classroom_id', this.form.classroom_id);
                        const res = await fetch('{{ route('teacher.materials.nextMeeting') }}?' + params.toString(), {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.suggestedMeeting = data.next || 1;
                        this.form.meeting_number = this.suggestedMeeting;
                    } catch (e) {}
                },

                async generate() {
                    if (!this.canGenerate) {
                        this.error = 'Isi judul, topik, dan pilih minimal 1 format.';
                        return;
                    }
                    this.error = '';
                    this.loading = true;
                    window.aiLoader?.show(`AI sedang menyusun ${this.form.formats.length} format materi…`);
                    try {
                        const res = await fetch('{{ route('teacher.materials.generate.ajax') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                title: this.form.title,
                                topic: this.form.topic,
                                level: this.form.level,
                                custom_prompt: this.form.custom_prompt,
                                formats: this.form.formats,
                            }),
                        });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.message || 'AI gagal.');
                        this.outputs = (data.outputs || []).map(o => ({
                            format: o.format, label: o.label, content: o.content,
                        }));
                        this.partial = data.partial || [];
                        this.activeTab = 0;
                        this.step = 2;
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                        window.aiLoader?.hide();
                    }
                },

                async regenerate(i) {
                    const out = this.outputs[i];
                    if (!out) return;
                    this.loading = true;
                    window.aiLoader?.show(`Membuat ulang format "${out.label}"…`);
                    try {
                        const res = await fetch('{{ route('teacher.materials.generate.ajax') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                title: this.form.title,
                                topic: this.form.topic,
                                level: this.form.level,
                                custom_prompt: this.form.custom_prompt,
                                formats: [out.format],
                            }),
                        });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.message || 'AI gagal.');
                        if (data.outputs?.[0]) {
                            this.outputs[i].content = data.outputs[0].content;
                        }
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                        window.aiLoader?.hide();
                    }
                },

                setPrimary(i) {
                    if (i === 0) return;
                    const item = this.outputs.splice(i, 1)[0];
                    this.outputs.unshift(item);
                    this.activeTab = 0;
                },
                removeOutput(i) {
                    if (this.outputs.length <= 1) return;
                    this.outputs.splice(i, 1);
                    if (this.activeTab >= this.outputs.length) this.activeTab = this.outputs.length - 1;
                },
                async copyOutput(i) {
                    try {
                        await navigator.clipboard.writeText(this.outputs[i].content);
                        window.toast?.('Disalin ke clipboard');
                    } catch (e) {}
                },
            }
        }
    </script>
</x-app-layout>

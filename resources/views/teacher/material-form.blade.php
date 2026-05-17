<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">
            {{ $material ? 'Edit Materi' : 'Buat Materi Baru' }}
        </h2>
        <p class="text-sm text-slate-500">{{ $material ? 'Perbarui konten & format materi.' : 'Mulai dari topik atau biarkan AI yang menyusun draft.' }}</p>
    </x-slot>

    @php
        $formatsMeta = \App\Models\Material::formats();
        $bundle = $material ? $material->outputBundle() : [];
        if (empty($bundle)) {
            $bundle = [[
                'format' => $material->format ?? 'standard',
                'label'  => \App\Models\Material::formatLabel($material->format ?? 'standard'),
                'icon'   => \App\Models\Material::formatIcon($material->format ?? 'standard'),
                'content'=> $material->content ?? '',
            ]];
        }
    @endphp

    <div class="space-y-6">
        @if ($errors->any())
            <div class="glass border-rose-200/60 bg-rose-50/60 dark:bg-rose-900/40 px-4 py-3 text-rose-700 dark:text-rose-200 text-sm">
                @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
            </div>
        @endif

        <div class="glass p-6" x-data="materialEditor(@js($bundle), @js($formatsMeta))">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-1">{{ $material ? 'Perbarui Materi' : 'Form Materi' }}</h3>
            <p class="text-xs text-slate-500 mb-5">Setiap format yang ada bisa diedit terpisah. Format pertama menjadi konten utama.</p>

            <form method="POST" action="{{ $material ? route('teacher.materials.update', $material) : route('teacher.materials.store') }}" class="space-y-4">
                @csrf @if($material) @method('PUT') @endif
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Pertemuan ke-</label>
                        <input type="number" min="1" max="9999" name="meeting_number"
                               class="input-glass"
                               placeholder="Kosongkan untuk auto"
                               value="{{ old('meeting_number', $material->meeting_number ?? '') }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Judul</label>
                        <input name="title" required class="input-glass" value="{{ old('title', $material->title ?? '') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Topik</label>
                        <input name="topic" required class="input-glass" value="{{ old('topic', $material->topic ?? '') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Tingkat</label>
                        <select name="level" class="input-glass">
                            @foreach(['SD','SMP','SMA','Umum'] as $lv)
                                <option value="{{ $lv }}" @selected(old('level', $material->level ?? 'SMA') === $lv)>{{ $lv }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Tautkan ke Kelas</label>
                        <select name="classroom_id" class="input-glass">
                            <option value="">— Tanpa kelas —</option>
                            @foreach(($classrooms ?? collect()) as $c)
                                <option value="{{ $c->id }}" @selected(old('classroom_id', $material->classroom_id ?? '') == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Arahan khusus tersimpan --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
                        Arahan Khusus (custom prompt)
                        <span class="text-xs font-normal text-slate-400">— dipakai saat Generate ulang format</span>
                    </label>
                    <textarea name="custom_prompt" rows="3" maxlength="2000" class="input-glass text-sm"
                              placeholder="Misal: fokus pada perspektif Kristen Protestan, gunakan studi kasus Kalimantan…">{{ old('custom_prompt', $material->custom_prompt ?? '') }}</textarea>
                </div>

                {{-- Tabs format --}}
                <div>
                    <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                            Konten per Format
                        </label>
                        <button type="button" class="btn-ghost text-xs" @click="showAdd = !showAdd">
                            <x-icon name="plus" class="w-3.5 h-3.5"/> Tambah format
                        </button>
                    </div>

                    {{-- Add format selector --}}
                    <div x-show="showAdd" x-cloak class="mb-3 p-3 rounded-xl bg-white/40 dark:bg-slate-800/30 border border-white/60 dark:border-white/10">
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <template x-for="(meta, key) in availableFormats()" :key="key">
                                <button type="button"
                                        @click="addFormat(key)"
                                        class="text-left flex items-start gap-2 p-2 rounded-lg hover:bg-emerald-50/70 dark:hover:bg-emerald-900/20 transition">
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200" x-text="meta.label"></span>
                                </button>
                            </template>
                            <p x-show="Object.keys(availableFormats()).length === 0" class="text-xs text-slate-500 col-span-full">
                                Semua format sudah ditambahkan.
                            </p>
                        </div>
                    </div>

                    {{-- Tab buttons --}}
                    <div class="flex gap-1 flex-wrap border-b border-white/50 dark:border-white/10 pb-2 mb-3">
                        <template x-for="(out, i) in outputs" :key="out.format">
                            <button type="button"
                                    @click="active = i"
                                    :class="active === i
                                        ? 'bg-emerald-500 text-white shadow'
                                        : 'bg-white/40 dark:bg-slate-800/40 text-slate-600 dark:text-slate-300 hover:bg-white/70'"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5">
                                <span x-text="out.label"></span>
                                <span class="text-[10px] opacity-70" x-show="i === 0">utama</span>
                            </button>
                        </template>
                    </div>

                    {{-- Editor area --}}
                    <template x-for="(out, i) in outputs" :key="'ed-'+out.format">
                        <div x-show="active === i" class="space-y-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="badge badge-emerald" x-text="out.label"></span>
                                <button type="button" class="btn-ghost text-xs" @click="setPrimary(i)" x-show="i !== 0">Jadikan utama</button>
                                <button type="button" class="btn-ghost text-xs text-rose-600" @click="removeOutput(i)" x-show="outputs.length > 1">
                                    <x-icon name="trash" class="w-3.5 h-3.5"/> Hapus format
                                </button>
                            </div>
                            <textarea x-model="out.content" rows="14" required class="input-glass font-mono text-sm leading-relaxed"></textarea>
                        </div>
                    </template>

                    {{-- Hidden inputs untuk submit --}}
                    <input type="hidden" name="format" :value="outputs[0]?.format || 'standard'">
                    <input type="hidden" name="content" :value="outputs[0]?.content || ''">
                    <template x-for="(out, i) in outputs" :key="'hf-'+out.format">
                        <span>
                            <input type="hidden" :name="`outputs[${i}][format]`" :value="out.format">
                            <input type="hidden" :name="`outputs[${i}][label]`"  :value="out.label">
                            <input type="hidden" :name="`outputs[${i}][content]`" :value="out.content">
                        </span>
                    </template>
                </div>

                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" {{ old('is_published', $material?->is_published ?? true) ? 'checked' : '' }} class="rounded">
                    Terbitkan ke siswa
                </label>
                <div class="flex items-center justify-end gap-2">
                    <a href="{{ $material ? route('teacher.materials.show', $material) : route('teacher.index') }}" class="btn-secondary">Batal</a>
                    <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> {{ $material ? 'Simpan Perubahan' : 'Simpan Materi' }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function materialEditor(initialOutputs, formatsMeta) {
            return {
                formatsMeta: formatsMeta || {},
                outputs: (initialOutputs || []).map(o => ({
                    format: o.format, label: o.label, content: o.content,
                })),
                active: 0,
                showAdd: false,

                availableFormats() {
                    const used = new Set(this.outputs.map(o => o.format));
                    const out = {};
                    for (const [k, v] of Object.entries(this.formatsMeta)) {
                        if (!used.has(k)) out[k] = v;
                    }
                    return out;
                },
                addFormat(key) {
                    if (!this.formatsMeta[key]) return;
                    this.outputs.push({
                        format: key,
                        label: this.formatsMeta[key].label,
                        content: '',
                    });
                    this.active = this.outputs.length - 1;
                    this.showAdd = false;
                },
                setPrimary(i) {
                    if (i === 0) return;
                    const item = this.outputs.splice(i, 1)[0];
                    this.outputs.unshift(item);
                    this.active = 0;
                },
                removeOutput(i) {
                    if (this.outputs.length <= 1) return;
                    this.outputs.splice(i, 1);
                    if (this.active >= this.outputs.length) this.active = this.outputs.length - 1;
                },
            }
        }
    </script>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">
            {{ $material ? 'Edit Materi' : 'Buat Materi Baru' }}
        </h2>
        <p class="text-sm text-slate-500">{{ $material ? 'Perbarui konten materi Anda.' : 'Mulai dari topik atau biarkan AI yang menyusun draft.' }}</p>
    </x-slot>

    <div class="space-y-6">
        @if ($errors->any())
            <div class="glass border-rose-200/60 bg-rose-50/60 dark:bg-rose-900/40 px-4 py-3 text-rose-700 dark:text-rose-200 text-sm">
                @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
            </div>
        @endif

        <div class="glass p-6">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-1">{{ $material ? 'Perbarui Materi' : 'Form Materi' }}</h3>
            <p class="text-xs text-slate-500 mb-5">Anda bebas mengedit konten sebelum/ saat dipublikasikan.</p>

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

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Konten Materi</label>
                    <textarea name="content" rows="18" required class="input-glass font-mono text-sm leading-relaxed">{{ old('content', $material->content ?? '') }}</textarea>
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
</x-app-layout>

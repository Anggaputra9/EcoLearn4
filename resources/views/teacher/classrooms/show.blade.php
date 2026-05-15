<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 truncate">{{ $classroom->name }}</h2>
                <p class="text-sm text-slate-500">{{ $classroom->subject ?: '—' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge badge-emerald font-mono">{{ $classroom->code }}</span>
                <button class="btn-secondary" @click="$dispatch('open-modal', 'class-edit')"><x-icon name="pencil" class="w-4 h-4"/> Edit</button>
                <button class="btn-danger" @click="$dispatch('open-modal', 'class-del')"><x-icon name="trash" class="w-4 h-4"/></button>
            </div>
        </div>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="glass p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Materi di Kelas Ini</h3>
                </div>
                @if($classroom->materials->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada materi yang ditautkan ke kelas ini. Tambahkan dari halaman <a href="{{ route('teacher.index') }}" class="text-emerald-600 hover:underline">Materi Saya</a>.</p>
                @else
                    <div class="grid sm:grid-cols-2 gap-3">
                        @foreach($classroom->materials as $m)
                            <a href="{{ route('teacher.materials.show', $m) }}" class="block p-4 rounded-xl bg-white/50 dark:bg-slate-800/40 hover:bg-white/80 dark:hover:bg-slate-800/70 transition border border-white/60 dark:border-white/10">
                                <span class="badge badge-emerald">{{ $m->level }}</span>
                                <p class="mt-2 font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $m->title }}</p>
                                <p class="text-xs text-slate-500">{{ $m->questions->count() }} soal</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($classroom->description)
                <div class="glass p-6">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-2">Deskripsi</h3>
                    <p class="text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">{{ $classroom->description }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="glass p-6">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3">Kode Bergabung</h3>
                <div class="flex items-center gap-2">
                    <code class="flex-1 text-2xl font-bold tracking-widest text-emerald-700 dark:text-emerald-300 bg-emerald-50/60 dark:bg-emerald-900/30 px-4 py-3 rounded-xl text-center">{{ $classroom->code }}</code>
                    <form method="POST" action="{{ route('teacher.classrooms.regen', $classroom) }}">
                        @csrf
                        <button class="btn-ghost p-3" title="Regenerasi kode"><x-icon name="history" class="w-5 h-5"/></button>
                    </form>
                </div>
                <p class="text-xs text-slate-500 mt-2">Bagikan kode ini ke siswa agar mereka bisa join.</p>
            </div>

            <div class="glass p-6">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3">Anggota ({{ $classroom->members->count() }})</h3>
                @if($classroom->members->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada siswa yang bergabung.</p>
                @else
                    <div class="space-y-2">
                        @foreach($classroom->members as $m)
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/40 dark:hover:bg-white/5 transition">
                                <img src="{{ $m->profile_photo_url }}" class="w-9 h-9 rounded-full ring-2 ring-emerald-200 dark:ring-emerald-700/40 object-cover">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate">{{ $m->name }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ $m->email }}</p>
                                </div>
                                <form method="POST" action="{{ route('teacher.classrooms.removeMember', [$classroom, $m->id]) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn-ghost p-1.5 text-rose-600" title="Keluarkan"><x-icon name="close" class="w-4 h-4"/></button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-modal-glass name="class-edit" title="Edit Kelas" max-width="lg">
        <form method="POST" action="{{ route('teacher.classrooms.update', $classroom) }}" class="space-y-3">
            @csrf @method('PUT')
            <div><label class="block text-sm font-medium mb-1">Nama</label>
                <input name="name" required value="{{ $classroom->name }}" class="input-glass"></div>
            <div><label class="block text-sm font-medium mb-1">Mapel</label>
                <input name="subject" value="{{ $classroom->subject }}" class="input-glass"></div>
            <div><label class="block text-sm font-medium mb-1">Deskripsi</label>
                <textarea name="description" rows="3" class="input-glass">{{ $classroom->description }}</textarea></div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded" {{ $classroom->is_active ? 'checked' : '' }}>
                Kelas aktif
            </label>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'class-edit')">Batal</button>
                <button class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal-glass>

    <x-modal-glass name="class-del" title="Hapus Kelas" max-width="md">
        <p class="text-slate-600 dark:text-slate-300">Hapus kelas <span class="font-semibold">{{ $classroom->name }}</span>? Materi tetap aman tapi tautan kelas akan dihapus.</p>
        <form method="POST" action="{{ route('teacher.classrooms.destroy', $classroom) }}" class="flex justify-end gap-2 mt-5">
            @csrf @method('DELETE')
            <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'class-del')">Batal</button>
            <button class="btn-danger"><x-icon name="trash" class="w-4 h-4"/> Hapus</button>
        </form>
    </x-modal-glass>
</x-app-layout>

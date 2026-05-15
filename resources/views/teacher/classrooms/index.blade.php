<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Kelas Saya</h2>
                <p class="text-sm text-slate-500">Buat kelas dan bagikan kode kepada siswa untuk bergabung.</p>
            </div>
            <button class="btn-primary" @click="$dispatch('open-modal', 'class-create')">
                <x-icon name="plus" class="w-4 h-4"/> Buat Kelas
            </button>
        </div>
    </x-slot>

    @if($classrooms->isEmpty())
        <div class="glass p-10 text-center text-slate-500">
            Anda belum punya kelas. <a href="#" @click.prevent="$dispatch('open-modal', 'class-create')" class="text-emerald-600 hover:underline">Buat kelas pertama →</a>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($classrooms as $c)
                <div class="glass p-5 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="badge {{ $c->is_active ? 'badge-emerald' : 'badge-slate' }}">{{ $c->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        <span class="text-xs font-mono px-2 py-0.5 rounded-md bg-emerald-100/70 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">{{ $c->code }}</span>
                    </div>
                    <h3 class="mt-3 font-semibold text-slate-800 dark:text-slate-100">{{ $c->name }}</h3>
                    <p class="text-sm text-slate-500 mt-1">{{ $c->subject ?: '—' }}</p>
                    <p class="mt-3 text-xs text-slate-500">{{ $c->members->count() }} siswa · {{ $c->materials->count() }} materi</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('teacher.classrooms.show', $c) }}" class="btn-primary text-sm py-1.5 px-3">Buka</a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $classrooms->links() }}</div>
    @endif

    <x-modal-glass name="class-create" title="Buat Kelas Baru" max-width="lg">
        <form method="POST" action="{{ route('teacher.classrooms.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Nama Kelas</label>
                <input name="name" required maxlength="150" class="input-glass" placeholder="Misal: Ekoteologi 12 IPA-1">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Mapel/Topik</label>
                <input name="subject" maxlength="150" class="input-glass" placeholder="Ekoteologi">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Deskripsi</label>
                <textarea name="description" rows="3" class="input-glass" placeholder="Singkat saja, tampil ke siswa."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'class-create')">Batal</button>
                <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Buat</button>
            </div>
        </form>
    </x-modal-glass>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800">Daftar Materi</h2>
        <p class="text-sm text-slate-500">Pilih materi untuk mulai mengerjakan esai.</p>
    </x-slot>

    <form method="GET" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-[1fr_180px_auto]">
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
        <button class="btn-secondary"><x-icon name="search" class="w-4 h-4"/> Filter</button>
    </form>

    @if($materials->isEmpty())
        <div class="glass p-10 text-center text-slate-500">Tidak ada materi yang cocok.</div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($materials as $m)
                <a href="{{ route('student.materials.show', $m) }}" class="glass p-5 hover:scale-[1.02] transition flex flex-col">
                    <span class="badge badge-emerald self-start">{{ $m->level }}</span>
                    <h3 class="mt-3 font-semibold text-slate-800">{{ $m->title }}</h3>
                    <p class="text-sm text-slate-500 mt-1">{{ $m->topic }}</p>
                    <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                        <span>Oleh {{ $m->teacher->name }}</span>
                        <span>{{ $m->questions->count() }} soal</span>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $materials->links() }}</div>
    @endif
</x-app-layout>

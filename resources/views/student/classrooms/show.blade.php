<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ $classroom->name }}</h2>
                <p class="text-sm text-slate-500">{{ $classroom->subject ?: '—' }} · oleh {{ $classroom->teacher->name }}</p>
            </div>
            <form method="POST" action="{{ route('student.classrooms.leave', $classroom) }}">
                @csrf @method('DELETE')
                <button class="btn-secondary"><x-icon name="logout" class="w-4 h-4"/> Keluar</button>
            </form>
        </div>
    </x-slot>

    @if($classroom->description)
        <div class="glass p-6 mb-6">
            <p class="text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">{{ $classroom->description }}</p>
        </div>
    @endif

    <div class="glass p-6">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-4">Materi</h3>
        @if($classroom->materials->isEmpty())
            <p class="text-sm text-slate-500">Guru belum menambahkan materi.</p>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($classroom->materials as $m)
                    <a href="{{ route('student.materials.show', $m) }}" class="block p-4 rounded-xl bg-white/50 dark:bg-slate-800/40 hover:bg-white/80 dark:hover:bg-slate-800/70 transition border border-white/60 dark:border-white/10">
                        <span class="badge badge-emerald">{{ $m->level }}</span>
                        <p class="mt-2 font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $m->title }}</p>
                        <p class="text-xs text-slate-500">{{ $m->questions->count() }} soal · {{ $m->exams->count() }} ujian</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Kelas Saya</h2>
                <p class="text-sm text-slate-500">Bergabung ke kelas guru menggunakan kode unik.</p>
            </div>
            <button class="btn-primary" @click="$dispatch('open-modal', 'class-join')">
                <x-icon name="plus" class="w-4 h-4"/> Gabung Kelas
            </button>
        </div>
    </x-slot>

    @if($classrooms->isEmpty())
        <div class="glass p-10 text-center text-slate-500">
            Anda belum bergabung ke kelas manapun. <a href="#" @click.prevent="$dispatch('open-modal', 'class-join')" class="text-emerald-600 hover:underline">Gabung sekarang →</a>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($classrooms as $c)
                <a href="{{ route('student.classrooms.show', $c) }}" class="glass p-5 hover:scale-[1.02] transition flex flex-col">
                    <span class="badge badge-emerald self-start">{{ $c->subject ?: 'Kelas' }}</span>
                    <h3 class="mt-3 font-semibold text-slate-800 dark:text-slate-100">{{ $c->name }}</h3>
                    <p class="text-sm text-slate-500 mt-1">Oleh {{ $c->teacher->name }}</p>
                    <p class="mt-3 text-xs text-slate-500">{{ $c->materials->count() }} materi</p>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $classrooms->links() }}</div>
    @endif

    <x-modal-glass name="class-join" title="Gabung Kelas" max-width="md">
        <form method="POST" action="{{ route('student.classrooms.join') }}" class="space-y-3">
            @csrf
            <p class="text-sm text-slate-600 dark:text-slate-300">Masukkan kode kelas yang diberikan oleh guru.</p>
            <input name="code" required maxlength="12" class="input-glass text-center font-mono uppercase tracking-widest text-lg" placeholder="ABC123">
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'class-join')">Batal</button>
                <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Gabung</button>
            </div>
        </form>
    </x-modal-glass>
</x-app-layout>

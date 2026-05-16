<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Histori Materi</h2>
                <p class="text-sm text-slate-500">Bekas materi tetap tersimpan di sini, bisa dipulihkan atau dihapus permanen.</p>
            </div>
            <a href="{{ route('teacher.index') }}" class="btn-secondary">
                <x-icon name="arrow-left" class="w-4 h-4"/> Kembali ke Materi
            </a>
        </div>
    </x-slot>

    <form method="GET" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-[1fr_180px_180px_auto]">
        <div class="relative">
            <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
            <input name="q" value="{{ $q }}" placeholder="Cari judul atau topik…" class="input-glass pl-9">
        </div>
        <select name="classroom_id" class="input-glass">
            <option value="">Semua kelas</option>
            @foreach($classrooms as $c)
                <option value="{{ $c->id }}" @selected((string)$classroomId === (string)$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="scope" class="input-glass">
            <option value="all" @selected($scope === 'all')>Semua (aktif + dihapus)</option>
            <option value="active" @selected($scope === 'active')>Hanya aktif</option>
            <option value="trashed" @selected($scope === 'trashed')>Hanya yang dihapus</option>
        </select>
        <button class="btn-secondary"><x-icon name="search" class="w-4 h-4"/> Filter</button>
    </form>

    @if(session('success'))
        <div class="glass border-emerald-200/60 bg-emerald-50/60 dark:bg-emerald-900/30 px-4 py-3 text-emerald-700 dark:text-emerald-200 text-sm mb-4">{{ session('success') }}</div>
    @endif

    @if($materials->isEmpty())
        <div class="glass p-10 text-center text-slate-500">Belum ada riwayat materi.</div>
    @else
        <div class="glass overflow-hidden">
            <div class="hidden md:grid grid-cols-[80px_1fr_140px_140px_180px_220px] gap-3 px-4 py-3 text-xs font-semibold uppercase text-slate-500 bg-white/40 dark:bg-slate-800/40 border-b border-white/60 dark:border-white/10">
                <div>Pertemuan</div>
                <div>Materi</div>
                <div>Kelas</div>
                <div>Tingkat</div>
                <div>Status</div>
                <div class="text-right">Aksi</div>
            </div>

            <ul class="divide-y divide-white/60 dark:divide-white/10">
                @foreach($materials as $m)
                    @php $isTrashed = ! is_null($m->deleted_at); @endphp
                    <li class="grid md:grid-cols-[80px_1fr_140px_140px_180px_220px] gap-3 px-4 py-4 items-start
                              {{ $isTrashed ? 'bg-rose-50/40 dark:bg-rose-900/10' : '' }}">
                        {{-- meeting --}}
                        <div>
                            @if($m->meeting_number)
                                <span class="badge badge-amber">#{{ $m->meeting_number }}</span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </div>

                        {{-- title --}}
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $m->title }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $m->topic }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Dibuat {{ $m->created_at->diffForHumans() }}
                                @if($isTrashed)
                                    · <span class="text-rose-600 dark:text-rose-300">Dihapus {{ $m->deleted_at->diffForHumans() }}</span>
                                @endif
                            </p>
                        </div>

                        {{-- classroom --}}
                        <div class="text-sm text-slate-600 dark:text-slate-300">
                            {{ $m->classroom?->name ?? '— Publik —' }}
                        </div>

                        {{-- level --}}
                        <div>
                            <span class="badge badge-emerald">{{ $m->level }}</span>
                        </div>

                        {{-- status --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            @if($isTrashed)
                                <span class="badge badge-rose">Di-arsipkan</span>
                            @elseif($m->is_published)
                                <span class="badge badge-emerald">Publik</span>
                            @else
                                <span class="badge badge-amber">Draft</span>
                            @endif
                            <span class="text-[11px] text-slate-500">{{ $m->questions->count() }} soal</span>
                        </div>

                        {{-- actions --}}
                        <div class="flex items-center justify-end gap-2 flex-wrap">
                            @if($isTrashed)
                                <form method="POST" action="{{ route('teacher.materials.restore', $m->id) }}">
                                    @csrf
                                    <button class="btn-secondary text-sm py-1.5 px-3" title="Pulihkan">
                                        <x-icon name="arrow-left" class="w-4 h-4"/> Pulihkan
                                    </button>
                                </form>
                                <button class="btn-danger text-sm py-1.5 px-3"
                                        @click="$dispatch('open-modal', 'mat-purge-{{ $m->id }}')">
                                    <x-icon name="trash" class="w-4 h-4"/> Hapus Permanen
                                </button>

                                <x-confirm-modal
                                    name="mat-purge-{{ $m->id }}"
                                    title="Hapus Permanen Materi"
                                    tone="danger"
                                    icon="trash"
                                    confirm-text="Hapus Permanen"
                                    :action="route('teacher.materials.force', $m->id)"
                                    method="DELETE"
                                    message="Hapus PERMANEN materi <strong>{{ e($m->title) }}</strong>? Tindakan ini tidak bisa dibatalkan. Semua soal & jawaban siswa terkait akan ikut hilang." />
                            @else
                                <a href="{{ route('teacher.materials.show', $m) }}" class="btn-primary text-sm py-1.5 px-3">Buka</a>
                                <button class="btn-ghost text-sm py-1.5 px-3 text-rose-600"
                                        @click="$dispatch('open-modal', 'mat-arch-{{ $m->id }}')" title="Arsipkan">
                                    <x-icon name="trash" class="w-4 h-4"/>
                                </button>
                                <x-confirm-modal
                                    name="mat-arch-{{ $m->id }}"
                                    title="Arsipkan Materi"
                                    tone="danger"
                                    icon="trash"
                                    confirm-text="Ya, Arsipkan"
                                    :action="route('teacher.materials.destroy', $m)"
                                    method="DELETE"
                                    message="Materi <strong>{{ e($m->title) }}</strong> akan dipindah ke histori (bisa dipulihkan kapan saja)." />
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="mt-6">{{ $materials->links() }}</div>
    @endif
</x-app-layout>

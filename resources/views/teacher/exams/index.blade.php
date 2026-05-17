<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Ujian Saya</h2>
            <p class="text-sm text-slate-500">Kelola ujian dari seluruh materi & kelas Anda.</p>
        </div>
    </x-slot>

    {{-- Stat ringkas --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        @php
            $cards = [
                ['Total',       $stats['total'],     'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',    'menu-list'],
                ['Published',   $stats['published'], 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', 'check'],
                ['Draft',       $stats['draft'],     'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', 'pencil'],
                ['Closed',      $stats['closed'],    'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',     'shield'],
            ];
        @endphp
        @foreach($cards as [$label, $value, $cls, $ic])
            <div class="glass p-4 flex items-center gap-3">
                <div class="w-10 h-10 grid place-items-center rounded-xl {{ $cls }}">
                    <x-icon :name="$ic" class="w-5 h-5"/>
                </div>
                <div>
                    <p class="text-xs text-slate-500">{{ $label }}</p>
                    <p class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ $value }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <form method="GET" class="glass p-4 mb-5 grid gap-3 sm:grid-cols-[1fr_180px_180px_auto]">
        <div class="relative">
            <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
            <input name="q" value="{{ $q }}" placeholder="Cari ujian / materi…" class="input-glass pl-9">
        </div>
        <select name="status" class="input-glass">
            <option value="">Semua status</option>
            @foreach(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $k => $v)
                <option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="material_id" class="input-glass">
            <option value="">Semua materi</option>
            @foreach($materials as $m)
                <option value="{{ $m->id }}" @selected((string)$matId === (string)$m->id)>{{ $m->title }}</option>
            @endforeach
        </select>
        <button class="btn-secondary"><x-icon name="search" class="w-4 h-4"/> Filter</button>
    </form>

    @if($exams->isEmpty())
        <div class="glass p-10 text-center text-slate-500">
            Belum ada ujian. Buat ujian dari halaman materi terlebih dahulu.
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($exams as $exam)
                <div class="glass p-5 flex flex-col">
                    <div class="flex items-center gap-2 flex-wrap">
                        @php
                            $badge = match($exam->status) {
                                'published' => 'badge-emerald',
                                'closed'    => 'badge-rose',
                                default     => 'badge-amber',
                            };
                        @endphp
                        <span class="badge {{ $badge }}">{{ ucfirst($exam->status) }}</span>
                        @if($exam->classroom)
                            <span class="badge badge-violet">{{ $exam->classroom->name }}</span>
                        @endif
                        <span class="text-xs text-slate-500">
                            <x-icon name="clock" class="w-3 h-3 inline -mt-0.5"/>
                            {{ $exam->duration_minutes ?: '∞' }} mnt
                        </span>
                    </div>

                    <h3 class="mt-3 font-semibold text-slate-800 dark:text-slate-100 line-clamp-2">{{ $exam->title }}</h3>
                    <p class="text-sm text-slate-500 mt-1 truncate">Materi: {{ $exam->material->title ?? '—' }}</p>

                    <p class="text-xs text-slate-400 mt-3">
                        {{ $exam->attempts_count }} peserta · {{ $exam->created_at->diffForHumans() }}
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('teacher.exams.show', $exam) }}" class="btn-primary text-sm py-1.5 px-3">Buka</a>
                        <a href="{{ route('teacher.materials.show', $exam->material_id) }}?tab=soal" class="btn-secondary text-sm py-1.5 px-3">Materi</a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $exams->links() }}</div>
    @endif
</x-app-layout>

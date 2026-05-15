<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Ujian Saya</h2>
            <p class="text-sm text-slate-500">Daftar ujian yang tersedia & yang sudah pernah Anda kerjakan.</p>
        </div>
    </x-slot>

    {{-- Filter & search --}}
    <form method="GET" class="glass p-4 mb-5 grid gap-3 sm:grid-cols-[1fr_auto]">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="relative">
            <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
            <input name="q" value="{{ $q }}" placeholder="Cari judul ujian atau materi…" class="input-glass pl-9">
        </div>
        <button class="btn-secondary"><x-icon name="search" class="w-4 h-4"/> Cari</button>
    </form>

    {{-- Tab status --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @php
            $tabs = [
                'live'     => ['label' => 'Sedang Berlangsung', 'icon' => 'play'],
                'upcoming' => ['label' => 'Akan Datang',         'icon' => 'clock'],
                'past'     => ['label' => 'Telah Selesai',       'icon' => 'check'],
                'all'      => ['label' => 'Semua',               'icon' => 'menu-list'],
            ];
        @endphp
        @foreach($tabs as $key => $t)
            @php
                $url = request()->fullUrlWithQuery(['tab' => $key]);
                $active = $tab === $key;
            @endphp
            <a href="{{ $url }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition
                      {{ $active ? 'bg-emerald-500 text-white shadow' : 'bg-white/60 dark:bg-slate-800/40 text-slate-600 dark:text-slate-300 hover:bg-white/80 dark:hover:bg-slate-800/70 border border-white/60 dark:border-white/10' }}">
                <x-icon :name="$t['icon']" class="w-4 h-4"/> {{ $t['label'] }}
            </a>
        @endforeach
    </div>

    @if($exams->isEmpty())
        <div class="glass p-10 text-center text-slate-500">
            Tidak ada ujian pada kategori ini.
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($exams as $exam)
                @php
                    $att = $attempts[$exam->id] ?? null;
                    $isLive = $exam->status === 'published' && $exam->isOpenNow();
                    $url = $att
                        ? route('student.exams.result', $exam)
                        : route('student.exams.lobby', $exam);
                @endphp
                <a href="{{ $url }}" class="glass p-5 hover:scale-[1.01] transition flex flex-col">
                    <div class="flex items-center gap-2 flex-wrap">
                        @if($isLive)
                            <span class="badge badge-emerald inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> Live
                            </span>
                        @elseif($exam->status === 'closed')
                            <span class="badge badge-rose">Tutup</span>
                        @else
                            <span class="badge badge-amber">Belum Mulai</span>
                        @endif
                        <span class="text-xs text-slate-500">
                            <x-icon name="clock" class="w-3 h-3 inline -mt-0.5"/>
                            {{ $exam->duration_minutes ? $exam->duration_minutes.' mnt' : 'Tanpa batas' }}
                        </span>
                    </div>

                    <h3 class="mt-3 font-semibold text-slate-800 dark:text-slate-100 line-clamp-2">{{ $exam->title }}</h3>
                    <p class="text-sm text-slate-500 mt-1 truncate">
                        Materi: {{ $exam->material->title ?? '—' }}
                        @if($exam->classroom) · {{ $exam->classroom->name }} @endif
                    </p>

                    <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                        <div>
                            @if($exam->starts_at)
                                Mulai {{ $exam->starts_at->isoFormat('D MMM, HH:mm') }}
                            @endif
                        </div>
                        @if($att)
                            @if($att->status === 'submitted' && $att->total_score !== null)
                                <span class="font-bold text-emerald-700 dark:text-emerald-300">{{ $att->total_score }}/100</span>
                            @elseif($att->status === 'in_progress')
                                <span class="badge badge-amber text-[10px]">Sedang dikerjakan</span>
                            @elseif($att->status === 'disqualified')
                                <span class="badge badge-rose text-[10px]">Diskualifikasi</span>
                            @elseif($att->status === 'expired')
                                <span class="badge badge-slate text-[10px]">Waktu habis</span>
                            @endif
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $exams->links() }}</div>
    @endif
</x-app-layout>

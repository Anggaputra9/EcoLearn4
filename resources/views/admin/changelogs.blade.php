<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Changelog</h2>
                <p class="text-sm text-slate-500">
                    Riwayat perubahan & rilis Eko-Scribe — dibaca langsung dari
                    <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-xs">CHANGELOG.md</code>.
                </p>
            </div>
            <span class="badge badge-emerald">
                <x-icon name="doc-text" class="w-4 h-4"/> Read-only
            </span>
        </div>
    </x-slot>

    <form method="GET" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-[1fr_220px_auto]">
        <div class="relative">
            <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
            <input name="q" value="{{ $q }}" placeholder="Cari versi atau isi catatan…" class="input-glass pl-9">
        </div>
        <select name="kind" class="input-glass">
            <option value="">Semua jenis</option>
            @foreach(['major','minor','patch','hotfix'] as $k)
                <option value="{{ $k }}" @selected($kind === $k)>{{ ucfirst($k) }}</option>
            @endforeach
        </select>
        <button class="btn-secondary"><x-icon name="search" class="w-4 h-4"/> Filter</button>
    </form>

    @if($changelogs->isEmpty())
        <div class="glass p-10 text-center text-slate-500">
            Belum ada catatan rilis di <code>CHANGELOG.md</code>.
        </div>
    @else
        <div class="space-y-4">
            @foreach($changelogs as $c)
                <div class="glass p-5">
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <span class="badge badge-emerald font-semibold">v{{ $c->version }}</span>
                        <span class="badge {{ ['major'=>'badge-rose','minor'=>'badge-sky','patch'=>'badge-violet','hotfix'=>'badge-amber'][$c->kind] ?? 'badge-emerald' }}">
                            {{ ucfirst($c->kind) }}
                        </span>
                        @if($c->released_at)
                            <span class="text-xs text-slate-500">
                                <x-icon name="clock" class="w-3.5 h-3.5 inline -mt-0.5"/>
                                {{ $c->released_at->isoFormat('D MMMM Y') }}
                            </span>
                        @endif
                    </div>
                    <div class="changelog-prose text-sm text-slate-700 dark:text-slate-200 leading-relaxed">
                        {!! $c->html !!}
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $changelogs->links() }}</div>
    @endif

    <style>
        .changelog-prose h3 { font-size: 0.95rem; font-weight: 600; margin-top: 0.75rem; margin-bottom: 0.25rem; }
        .changelog-prose ul { list-style: disc; padding-left: 1.25rem; margin: 0.25rem 0 0.5rem; }
        .changelog-prose li { margin: 0.15rem 0; }
        .changelog-prose code { background: rgba(15,23,42,0.06); padding: 0.05rem 0.3rem; border-radius: 0.25rem; font-size: 0.85em; }
        .dark .changelog-prose code { background: rgba(255,255,255,0.08); }
        .changelog-prose strong { color: rgb(15 23 42); }
        .dark .changelog-prose strong { color: rgb(241 245 249); }
        .changelog-prose a { color: rgb(5 150 105); text-decoration: underline; }
    </style>
</x-app-layout>

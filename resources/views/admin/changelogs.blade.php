<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Changelog</h2>
                <p class="text-sm text-slate-500">Riwayat perubahan & rilis Eko-Scribe.</p>
            </div>
            <button class="btn-primary" @click="$dispatch('open-modal', 'cl-create')">
                <x-icon name="plus" class="w-4 h-4"/> Catat Rilis
            </button>
        </div>
    </x-slot>

    <form method="GET" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-[1fr_220px_auto]">
        <div class="relative">
            <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
            <input name="q" value="{{ $q }}" placeholder="Cari versi atau judul…" class="input-glass pl-9">
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
        <div class="glass p-10 text-center text-slate-500">Belum ada catatan rilis.</div>
    @else
        <div class="space-y-4">
            @foreach($changelogs as $c)
                <div class="glass p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
                        <div class="flex items-center gap-3">
                            <span class="badge badge-emerald font-semibold">v{{ $c->version }}</span>
                            <span class="badge {{ ['major'=>'badge-rose','minor'=>'badge-sky','patch'=>'badge-violet','hotfix'=>'badge-amber'][$c->kind] ?? 'badge-emerald' }}">{{ ucfirst($c->kind) }}</span>
                            <span class="text-xs text-slate-500">{{ $c->released_at->isoFormat('D MMMM Y') }}</span>
                        </div>
                        <div class="flex gap-1">
                            <button class="btn-ghost" @click="$dispatch('open-modal', 'cl-edit-{{ $c->id }}')">
                                <x-icon name="pencil" class="w-4 h-4"/>
                            </button>
                            <button class="btn-ghost text-rose-600" @click="$dispatch('open-modal', 'cl-del-{{ $c->id }}')">
                                <x-icon name="trash" class="w-4 h-4"/>
                            </button>
                        </div>
                    </div>
                    <h3 class="font-semibold text-slate-800">{{ $c->title }}</h3>
                    <pre class="mt-2 text-sm text-slate-600 whitespace-pre-wrap font-sans leading-relaxed">{{ $c->notes }}</pre>
                </div>

                <x-modal-glass name="cl-edit-{{ $c->id }}" title="Edit Changelog" max-width="2xl">
                    <form method="POST" action="{{ url('/admin/changelogs/'.$c->id) }}" class="space-y-3">
                        @csrf @method('PUT')
                        @include('admin.partials.changelog-fields', ['cl' => $c])
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'cl-edit-{{ $c->id }}')">Batal</button>
                            <button class="btn-primary">Simpan</button>
                        </div>
                    </form>
                </x-modal-glass>

                <x-modal-glass name="cl-del-{{ $c->id }}" title="Hapus Changelog" max-width="md">
                    <p class="text-slate-600">Hapus catatan rilis <span class="font-semibold">v{{ $c->version }}</span>?</p>
                    <form method="POST" action="{{ url('/admin/changelogs/'.$c->id) }}" class="flex justify-end gap-2 mt-5">
                        @csrf @method('DELETE')
                        <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'cl-del-{{ $c->id }}')">Batal</button>
                        <button class="btn-danger"><x-icon name="trash" class="w-4 h-4"/> Hapus</button>
                    </form>
                </x-modal-glass>
            @endforeach
        </div>
        <div class="mt-6">{{ $changelogs->links() }}</div>
    @endif

    <x-modal-glass name="cl-create" title="Catat Rilis Baru" max-width="2xl">
        <form method="POST" action="{{ url('/admin/changelogs') }}" class="space-y-3">
            @csrf
            @include('admin.partials.changelog-fields', ['cl' => null])
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'cl-create')">Batal</button>
                <button class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal-glass>
</x-app-layout>

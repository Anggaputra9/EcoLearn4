<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Mail Key Pool</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Banyak API key per provider mail. Sistem otomatis berpindah saat satu kehabisan kuota.</p>
            </div>
            <button type="button" class="btn-primary" @click="$dispatch('open-modal', 'mk-create')">
                <x-icon name="plus" class="w-4 h-4"/> Tambah Key
            </button>
        </div>
    </x-slot>

    @php $grouped = $keys->groupBy('provider'); @endphp

    @if($keys->isEmpty())
        <div class="glass p-10 text-center text-slate-500 dark:text-slate-400">
            Belum ada mail key. <a href="#" @click.prevent="$dispatch('open-modal', 'mk-create')" class="text-emerald-600 hover:underline">Tambah key pertama →</a>
        </div>
    @else
        @foreach($grouped as $providerKey => $list)
            <div class="glass p-5 mb-6">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3">{{ $providers[$providerKey] ?? $providerKey }}</h3>
                <div class="space-y-2">
                    @foreach($list as $k)
                        <div class="rounded-xl bg-white/50 dark:bg-slate-800/40 border border-white/60 dark:border-white/10 p-3 flex flex-wrap items-center gap-3">
                            <span class="text-xs px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-200 font-mono">#{{ $k->priority }}</span>
                            <span class="badge {{ $k->is_active ? 'badge-emerald' : 'badge-slate' }}">{{ $k->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $k->label }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">{{ $k->maskedKey() }}</p>
                            </div>
                            <div class="text-right">
                                @if($k->quota_limit)
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Sisa <span class="font-bold text-slate-800 dark:text-slate-100">{{ $k->quotaRemaining() }}</span> / {{ $k->quota_limit }}</p>
                                @else
                                    <span class="badge badge-sky">Tak terbatas</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1">
                                <form method="POST" action="{{ url('/admin/mail-keys/'.$k->id.'/reset-quota') }}">@csrf
                                    <button class="btn-ghost p-2" title="Reset kuota"><x-icon name="history" class="w-4 h-4"/></button>
                                </form>
                                <button type="button" class="btn-ghost p-2" @click="$dispatch('open-modal', 'mk-edit-{{ $k->id }}')"><x-icon name="pencil" class="w-4 h-4"/></button>
                                <button type="button" class="btn-ghost p-2 text-rose-600" @click="$dispatch('open-modal', 'mk-del-{{ $k->id }}')"><x-icon name="trash" class="w-4 h-4"/></button>
                            </div>
                            @if($k->last_error)
                                <p class="w-full mt-1 text-xs text-rose-600 dark:text-rose-300 truncate">⚠ {{ $k->last_error }}</p>
                            @endif
                        </div>

                        <x-modal-glass name="mk-edit-{{ $k->id }}" title="Edit Mail Key" max-width="lg">
                            <form method="POST" action="{{ url('/admin/mail-keys/'.$k->id) }}" class="space-y-3">
                                @csrf @method('PUT')
                                @include('admin.partials.mail-key-fields', ['providers' => $providers, 'k' => $k])
                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'mk-edit-{{ $k->id }}')">Batal</button>
                                    <button class="btn-primary">Simpan</button>
                                </div>
                            </form>
                        </x-modal-glass>

                        <x-modal-glass name="mk-del-{{ $k->id }}" title="Hapus Mail Key" max-width="md">
                            <p class="text-slate-600 dark:text-slate-300">Hapus key <span class="font-semibold">{{ $k->label }}</span>?</p>
                            <form method="POST" action="{{ url('/admin/mail-keys/'.$k->id) }}" class="flex justify-end gap-2 mt-5">
                                @csrf @method('DELETE')
                                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'mk-del-{{ $k->id }}')">Batal</button>
                                <button class="btn-danger"><x-icon name="trash" class="w-4 h-4"/> Hapus</button>
                            </form>
                        </x-modal-glass>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

    <x-modal-glass name="mk-create" title="Tambah Mail Key" max-width="lg">
        <form method="POST" action="{{ url('/admin/mail-keys') }}" class="space-y-3">
            @csrf
            @include('admin.partials.mail-key-fields', ['providers' => $providers, 'k' => null])
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'mk-create')">Batal</button>
                <button class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal-glass>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Kelola Menu</h2>
                <p class="text-sm text-slate-500">Atur menu navigasi dinamis per peran.</p>
            </div>
            <button class="btn-primary" @click="$dispatch('open-modal', 'menu-create')">
                <x-icon name="plus" class="w-4 h-4"/> Tambah Menu
            </button>
        </div>
    </x-slot>

    <form method="GET" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-[1fr_220px_auto]">
        <div class="relative">
            <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
            <input name="q" value="{{ $q }}" placeholder="Cari nama menu atau URL…" class="input-glass pl-9">
        </div>
        <select name="role_id" class="input-glass">
            <option value="">Semua peran</option>
            @foreach($roles as $r)
                <option value="{{ $r->id }}" @selected((string)$roleId === (string)$r->id)>{{ $r->nama_role }}</option>
            @endforeach
        </select>
        <button class="btn-secondary"><x-icon name="search" class="w-4 h-4"/> Filter</button>
    </form>

    <div class="glass overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-white/40 text-xs uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="px-5 py-3 text-left">Nama Menu</th>
                        <th class="px-5 py-3 text-left">URL</th>
                        <th class="px-5 py-3 text-left">Untuk Peran</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/40">
                    @forelse($menus as $m)
                        <tr class="hover:bg-white/40 transition">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $m->nama_menu }}</td>
                            <td class="px-5 py-3 text-emerald-600 font-mono text-xs">{{ $m->url }}</td>
                            <td class="px-5 py-3"><span class="badge badge-violet">{{ $m->nama_role }}</span></td>
                            <td class="px-5 py-3 text-right">
                                <button class="btn-ghost" @click="$dispatch('open-modal', 'menu-edit-{{ $m->id }}')">
                                    <x-icon name="pencil" class="w-4 h-4"/>
                                </button>
                                <button class="btn-ghost text-rose-600" @click="$dispatch('open-modal', 'menu-del-{{ $m->id }}')">
                                    <x-icon name="trash" class="w-4 h-4"/>
                                </button>
                            </td>
                        </tr>

                        <x-modal-glass name="menu-edit-{{ $m->id }}" title="Edit Menu" max-width="2xl">
                            <form method="POST" action="{{ url('/admin/menus/'.$m->id) }}" class="space-y-3">
                                @csrf @method('PUT')
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Menu</label>
                                        <input name="nama_menu" value="{{ $m->nama_menu }}" required class="input-glass">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">URL</label>
                                        <input name="url" value="{{ $m->url }}" required class="input-glass">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Untuk Peran</label>
                                    <select name="role_id" class="input-glass">
                                        @foreach($roles as $r)
                                            <option value="{{ $r->id }}" @selected($r->id == $m->role_id)>{{ $r->nama_role }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Konten Halaman <span class="text-slate-400">(opsional)</span></label>
                                    <textarea name="konten" rows="6" class="input-glass">{{ $m->konten }}</textarea>
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'menu-edit-{{ $m->id }}')">Batal</button>
                                    <button class="btn-primary">Simpan</button>
                                </div>
                            </form>
                        </x-modal-glass>

                        <x-confirm-modal
                            name="menu-del-{{ $m->id }}"
                            title="Hapus Menu"
                            tone="danger"
                            icon="trash"
                            confirm-text="Ya, Hapus"
                            :action="url('/admin/menus/'.$m->id)"
                            method="DELETE"
                            :message="'Yakin ingin menghapus menu <strong>'.e($m->nama_menu).'</strong>?'" />
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Tidak ada menu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $menus->links() }}</div>
    </div>

    <x-modal-glass name="menu-create" title="Tambah Menu" max-width="2xl">
        <form method="POST" action="{{ url('/admin/menus') }}" class="space-y-3">
            @csrf
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Menu</label>
                    <input name="nama_menu" required class="input-glass">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">URL</label>
                    <input name="url" required class="input-glass" placeholder="/contoh">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Untuk Peran</label>
                <select name="role_id" class="input-glass" required>
                    @foreach($roles as $r) <option value="{{ $r->id }}">{{ $r->nama_role }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Konten Halaman <span class="text-slate-400">(opsional)</span></label>
                <textarea name="konten" rows="5" class="input-glass"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'menu-create')">Batal</button>
                <button class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal-glass>
</x-app-layout>

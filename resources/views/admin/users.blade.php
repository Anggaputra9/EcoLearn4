<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Kelola Pengguna</h2>
                <p class="text-sm text-slate-500">Kelola akun administrator, guru, dan siswa.</p>
            </div>
            <button class="btn-primary" @click="$dispatch('open-modal', 'user-create')">
                <x-icon name="plus" class="w-4 h-4"/> Tambah Pengguna
            </button>
        </div>
    </x-slot>

    {{-- Filter --}}
    <form method="GET" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-[1fr_220px_auto]">
        <div class="relative">
            <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
            <input name="q" value="{{ $q }}" placeholder="Cari nama atau email…" class="input-glass pl-9">
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
                        <th class="px-5 py-3 text-left">Pengguna</th>
                        <th class="px-5 py-3 text-left">Email</th>
                        <th class="px-5 py-3 text-left">Peran</th>
                        <th class="px-5 py-3 text-left">Bergabung</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/40">
                    @forelse($users as $u)
                        <tr class="hover:bg-white/40 transition">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $u->name }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $u->email }}</td>
                            <td class="px-5 py-3">
                                @php
                                    $cls = match((int)$u->role_id) {
                                        1 => 'badge-violet', 2 => 'badge-emerald', default => 'badge-sky'
                                    };
                                @endphp
                                <span class="badge {{ $cls }}">{{ $u->nama_role }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ \Carbon\Carbon::parse($u->created_at)->isoFormat('D MMM Y') }}</td>
                            <td class="px-5 py-3 text-right">
                                <button class="btn-ghost"
                                        @click="$dispatch('open-modal', 'user-edit-{{ $u->id }}')">
                                    <x-icon name="pencil" class="w-4 h-4"/>
                                </button>
                                @if($u->id !== auth()->id())
                                    <button class="btn-ghost text-rose-600"
                                            @click="$dispatch('open-modal', 'user-del-{{ $u->id }}')">
                                        <x-icon name="trash" class="w-4 h-4"/>
                                    </button>
                                @endif
                            </td>
                        </tr>

                        {{-- Modal Edit --}}
                        <x-modal-glass name="user-edit-{{ $u->id }}" title="Edit Pengguna" max-width="lg">
                            <form method="POST" action="{{ url('/admin/users/'.$u->id) }}" class="space-y-3">
                                @csrf @method('PUT')
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama</label>
                                    <input name="name" value="{{ $u->name }}" required class="input-glass">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                    <input name="email" type="email" value="{{ $u->email }}" required class="input-glass">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Peran</label>
                                    <select name="role_id" class="input-glass">
                                        @foreach($roles as $r)
                                            <option value="{{ $r->id }}" @selected($r->id == $u->role_id)>{{ $r->nama_role }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi Baru <span class="text-slate-400">(opsional)</span></label>
                                    <input name="password" type="password" minlength="8" class="input-glass" placeholder="Kosongkan jika tidak diubah">
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'user-edit-{{ $u->id }}')">Batal</button>
                                    <button class="btn-primary">Simpan</button>
                                </div>
                            </form>
                        </x-modal-glass>

                        {{-- Modal Hapus --}}
                        <x-confirm-modal
                            name="user-del-{{ $u->id }}"
                            title="Hapus Pengguna"
                            tone="danger"
                            icon="trash"
                            confirm-text="Ya, Hapus Pengguna"
                            :action="url('/admin/users/'.$u->id)"
                            method="DELETE"
                            :message="'Yakin ingin menghapus <strong>'.e($u->name).'</strong>? Tindakan ini tidak dapat dibatalkan.'" />

                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Tidak ada pengguna yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $users->links() }}</div>
    </div>

    {{-- Modal Tambah --}}
    <x-modal-glass name="user-create" title="Tambah Pengguna" max-width="lg">
        <form method="POST" action="{{ url('/admin/users') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama</label>
                <input name="name" required class="input-glass">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input name="email" type="email" required class="input-glass">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Peran</label>
                <select name="role_id" class="input-glass" required>
                    @foreach($roles as $r)
                        <option value="{{ $r->id }}">{{ $r->nama_role }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi</label>
                <input name="password" type="password" required minlength="8" class="input-glass">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'user-create')">Batal</button>
                <button class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal-glass>
</x-app-layout>

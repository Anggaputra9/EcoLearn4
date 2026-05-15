<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800">Profil Saya</h2>
        <p class="text-sm text-slate-500">Atur informasi akun & foto profil Anda.</p>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Foto profil --}}
        <div class="glass p-6">
            <h3 class="font-semibold text-slate-800 mb-1">Foto Profil</h3>
            <p class="text-xs text-slate-500 mb-5">JPG/PNG/WebP, maks 2 MB.</p>

            <div class="flex flex-col items-center text-center">
                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}"
                     class="w-32 h-32 rounded-full ring-4 ring-emerald-200 object-cover shadow-lg">
                <p class="mt-3 font-semibold text-slate-800">{{ $user->name }}</p>
                <p class="text-xs text-slate-500">{{ $user->email }}</p>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-5 space-y-3">
                @csrf @method('PATCH')
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">

                <label class="btn-secondary w-full cursor-pointer">
                    <x-icon name="photo" class="w-4 h-4"/>
                    <span>Pilih Foto Baru</span>
                    <input type="file" name="photo" accept="image/*" class="hidden"
                           onchange="this.form.submit()">
                </label>
            </form>

            @if($user->profile_photo_path)
                <button type="button" class="btn-ghost w-full text-rose-600 mt-2"
                        @click="$dispatch('open-modal', 'photo-del')">
                    <x-icon name="trash" class="w-4 h-4"/> Hapus Foto
                </button>
                <x-confirm-modal
                    name="photo-del"
                    title="Hapus Foto Profil"
                    tone="danger"
                    icon="trash"
                    confirm-text="Ya, Hapus Foto"
                    :action="route('profile.photo.delete')"
                    method="POST"
                    message="Foto profil akan dihapus dan diganti avatar default. Tindakan ini tidak dapat dibatalkan." />
            @endif
        </div>

        {{-- Info dasar --}}
        <div class="lg:col-span-2 glass p-6">
            <h3 class="font-semibold text-slate-800 mb-1">Informasi Akun</h3>
            <p class="text-xs text-slate-500 mb-5">Perbarui nama dan email Anda.</p>

            @if ($errors->any())
                <div class="rounded-lg border border-rose-200 bg-rose-50/60 px-4 py-3 mb-4 text-rose-700 text-sm">
                    @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama</label>
                    <input name="name" value="{{ old('name', $user->name) }}" required class="input-glass">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="input-glass">
                </div>
                <div class="flex justify-end">
                    <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Simpan</button>
                </div>
            </form>

            <hr class="my-6 border-white/40">

            <h3 class="font-semibold text-slate-800 mb-1">Ubah Kata Sandi</h3>
            <p class="text-xs text-slate-500 mb-5">Minimal 8 karakter.</p>

            <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi Saat Ini</label>
                    <input type="password" name="current_password" required class="input-glass">
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi Baru</label>
                        <input type="password" name="password" required minlength="8" class="input-glass">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Kata Sandi</label>
                        <input type="password" name="password_confirmation" required minlength="8" class="input-glass">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button class="btn-primary"><x-icon name="shield" class="w-4 h-4"/> Perbarui Sandi</button>
                </div>
            </form>

            <hr class="my-6 border-white/40">

            <h3 class="font-semibold text-rose-700 mb-1">Zona Berbahaya</h3>
            <p class="text-xs text-slate-500 mb-3">Menghapus akun bersifat permanen. Tindakan tidak dapat dibatalkan.</p>
            <button type="button" class="btn-danger" @click="$dispatch('open-modal', 'profile-delete')">
                <x-icon name="trash" class="w-4 h-4"/> Hapus Akun Saya
            </button>
        </div>
    </div>

    <x-modal-glass name="profile-delete" title="Hapus Akun Saya" max-width="md">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 grid place-items-center rounded-full bg-rose-100 dark:bg-rose-900/40 shrink-0">
                <x-icon name="trash" class="w-5 h-5 text-rose-600"/>
            </div>
            <div class="flex-1">
                <p class="text-slate-700 dark:text-slate-200 leading-relaxed">
                    Tindakan ini <strong>permanen</strong>. Semua materi, soal, hasil ujian, dan data terkait akan ikut terhapus.
                    Konfirmasi dengan memasukkan kata sandi Anda.
                </p>
            </div>
        </div>
        <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-3">
            @csrf @method('DELETE')
            <input type="password" name="password" required placeholder="Kata sandi Anda" class="input-glass">
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'profile-delete')">Batal</button>
                <button class="btn-danger"><x-icon name="trash" class="w-4 h-4"/> Hapus Permanen</button>
            </div>
        </form>
    </x-modal-glass>
</x-app-layout>

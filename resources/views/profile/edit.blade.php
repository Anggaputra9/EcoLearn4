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
                <form method="POST" action="{{ route('profile.photo.delete') }}" class="mt-2">
                    @csrf
                    <button class="btn-ghost w-full text-rose-600">
                        <x-icon name="trash" class="w-4 h-4"/> Hapus Foto
                    </button>
                </form>
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

    <x-modal-glass name="profile-delete" title="Hapus Akun" max-width="md">
        <p class="text-slate-600">Konfirmasi dengan kata sandi Anda. Semua data terkait akan ikut terhapus.</p>
        <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4 space-y-3">
            @csrf @method('DELETE')
            <input type="password" name="password" required placeholder="Kata sandi Anda" class="input-glass">
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'profile-delete')">Batal</button>
                <button class="btn-danger">Hapus Akun</button>
            </div>
        </form>
    </x-modal-glass>
</x-app-layout>

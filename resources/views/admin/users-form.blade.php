<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($user) ? __('?? Edit Pengguna') : __('? Tambah Pengguna Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-emerald-500">
                <div class="p-6 text-gray-900">
                    <form action="{{ isset($user) ? url('/admin/users/'.$user->id) : url('/admin/users') }}" method="POST">
                        @csrf
                        @if(isset($user))
                            @method('PUT')
                        @endif

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Nama Lengkap</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name ?? '') }}" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-emerald-500 focus:border-emerald-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Alamat Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-emerald-500 focus:border-emerald-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="role_id">Hak Akses (Role)</label>
                            <select name="role_id" id="role_id" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">-- Pilih Hak Akses --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ (old('role_id', $user->role_id ?? '') == $role->id) ? 'selected' : '' }}>
                                        {{ $role->nama_role }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                                Password {{ isset($user) ? '(Kosongkan jika tidak ingin mengubah)' : '' }}
                            </label>
                            <input type="password" name="password" id="password" {{ isset($user) ? '' : 'required' }}
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-emerald-500 focus:border-emerald-500">
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150">
                                {{ isset($user) ? 'Simpan Perubahan' : 'Tambahkan Pengguna' }}
                            </button>
                            <a href="{{ url('/admin/users') }}" class="inline-block align-baseline font-bold text-sm text-gray-500 hover:text-gray-800">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

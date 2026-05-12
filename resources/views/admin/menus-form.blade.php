<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($menu) ? __('?? Edit Menu & Halaman') : __('? Tambah Menu & Halaman Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-emerald-500">
                <div class="p-6 text-gray-900">
                    <form action="{{ isset($menu) ? url('/admin/menus/'.$menu->id) : url('/admin/menus') }}" method="POST">
                        @csrf
                        @if(isset($menu)) @method('PUT') @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="nama_menu">Nama Menu</label>
                                <input type="text" name="nama_menu" id="nama_menu" value="{{ old('nama_menu', $menu->nama_menu ?? '') }}" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="url">URL / Rute (Contoh: /siswa/materi)</label>
                                <input type="text" name="url" id="url" value="{{ old('url', $menu->url ?? '') }}" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="role_id">Ditampilkan Untuk (Hak Akses)</label>
                            <select name="role_id" id="role_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">-- Pilih Hak Akses --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ (old('role_id', $menu->role_id ?? '') == $role->id) ? 'selected' : '' }}>{{ $role->nama_role }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-6 border border-gray-200 p-4 rounded-md bg-gray-50">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="konten">?? Isi Halaman / Konten (Opsional)</label>
                            <p class="text-xs text-gray-500 mb-2">Tuliskan materi, instruksi ujian, atau pengumuman di sini. Anda tidak perlu memikirkan kode, ketik saja seperti biasa.</p>
                            <textarea name="konten" id="konten" rows="10" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-emerald-500 focus:border-emerald-500" placeholder="Ketik isi halaman Anda di sini...">{{ old('konten', $menu->konten ?? '') }}</textarea>
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-150">
                                {{ isset($menu) ? 'Simpan Perubahan' : 'Tambahkan Menu' }}
                            </button>
                            <a href="{{ url('/admin/menus') }}" class="inline-block align-baseline font-bold text-sm text-gray-500 hover:text-gray-800">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

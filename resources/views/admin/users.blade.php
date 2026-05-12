<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('?? Kelola Pengguna') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p class="font-bold">Berhasil</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p class="font-bold">Gagal</p>
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-emerald-500">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-700">Daftar Pengguna Sistem</h3>
                        <a href="{{ url('/admin/users/create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded text-sm transition duration-150">
                            + Tambah Pengguna
                        </a>
                    </div>
                    <table class="min-w-full leading-normal border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100 text-left text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 border-b border-gray-200">ID</th>
                                <th class="py-3 px-6 border-b border-gray-200">Nama</th>
                                <th class="py-3 px-6 border-b border-gray-200">Email</th>
                                <th class="py-3 px-6 border-b border-gray-200">Hak Akses (Role)</th>
                                <th class="py-3 px-6 border-b border-gray-200 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            @foreach($users as $user)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-3 px-6 text-left whitespace-nowrap">{{ $user->id }}</td>
                                <td class="py-3 px-6 text-left font-medium">{{ $user->name }}</td>
                                <td class="py-3 px-6 text-left">{{ $user->email }}</td>
                                <td class="py-3 px-6 text-left">
                                    <span class="bg-emerald-100 text-emerald-800 py-1 px-3 rounded-full text-xs font-semibold">
                                        {{ $user->nama_role }}
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <a href="{{ url('/admin/users/'.$user->id.'/edit') }}" class="flex items-center gap-1 bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 px-3 py-1.5 rounded-md text-sm font-semibold transition duration-150 shadow-sm">
                                            ?? Edit
                                        </a>
                                        <form action="{{ url('/admin/users/'.$user->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus pengguna ini?');" class="inline-block m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="flex items-center gap-1 bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-md text-sm font-semibold transition duration-150 shadow-sm">
                                                ??? Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

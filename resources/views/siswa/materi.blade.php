<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">?? Akses Materi Pelajaran</h2></x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-blue-500">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4 text-gray-700">Daftar Materi dari Guru</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($materis as $m)
                            <div class="border rounded-lg p-5 shadow-sm hover:shadow-md transition">
                                <h4 class="text-xl font-bold text-blue-600 mb-2">{{ $m->judul }}</h4>
                                <p class="text-sm text-gray-500 mb-4">????? Oleh: {{ $m->nama_guru }}</p>
                                <p class="text-gray-700 mb-4">{{ $m->deskripsi }}</p>
                                <a href="{{ asset('storage/'.$m->file_path) }}" target="_blank" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full text-center">
                                    Unduh / Buka Materi
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

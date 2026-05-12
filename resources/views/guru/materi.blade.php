<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">?? Kelola Materi Pelajaran</h2></x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success')) <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 font-bold">{{ session('success') }}</div> @endif
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-emerald-500 mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">Unggah Materi Baru</h3>
                    <form action="{{ url('/guru/materi') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4"><label class="block text-sm font-bold mb-2">Judul Materi</label><input type="text" name="judul" required class="border rounded w-full py-2 px-3 focus:ring-emerald-500"></div>
                        <div class="mb-4"><label class="block text-sm font-bold mb-2">Deskripsi Singkat</label><textarea name="deskripsi" class="border rounded w-full py-2 px-3 focus:ring-emerald-500"></textarea></div>
                        <div class="mb-4"><label class="block text-sm font-bold mb-2">File (PDF/Word/PPT - Maks 5MB)</label><input type="file" name="file_materi" required class="w-full"></div>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded">Unggah Materi</button>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg"><div class="p-6">
                <h3 class="text-lg font-bold mb-4">Materi yang Sudah Diunggah</h3>
                <ul class="divide-y divide-gray-200">
                    @foreach($materis as $m)
                        <li class="py-4 flex justify-between">
                            <div><p class="text-lg font-medium text-emerald-600">{{ $m->judul }}</p><p class="text-sm text-gray-500">{{ $m->deskripsi }}</p></div>
                            <a href="{{ asset('storage/'.$m->file_path) }}" target="_blank" class="bg-blue-100 text-blue-700 px-3 py-1 rounded font-bold text-sm h-fit">Lihat File</a>
                        </li>
                    @endforeach
                </ul>
            </div></div>
        </div>
    </div>
</x-app-layout>

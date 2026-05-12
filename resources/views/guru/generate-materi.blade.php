<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">? Generator Materi AI (Pembersih Otomatis)</h2></x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-emerald-500 p-6">
                
                <form method="POST" action="{{ url('/guru/generate-materi') }}" class="mb-6">
                    @csrf
                    <label class="block text-sm font-bold mb-2">Apa topik materi yang ingin dibuat?</label>
                    <input type="text" name="topik" value="{{ $topik }}" required class="w-full border-gray-300 rounded-md shadow-sm mb-4" placeholder="Misal: Ekosistem Laut">
                    <button type="submit" class="w-full bg-emerald-600 text-white font-bold py-3 rounded-md hover:bg-emerald-700">?? Mulai Generate Materi</button>
                </form>

                @if($hasil)
                <div class="mt-8 border-t pt-6">
                    <h3 class="text-lg font-bold text-emerald-800 mb-4">Preview Hasil (Sudah Bersih):</h3>
                    <div class="p-6 bg-gray-50 rounded-lg text-gray-800 whitespace-pre-wrap mb-6 border">
                        {{ $hasil }}
                    </div>

                    <form method="POST" action="{{ url('/guru/save-pdf') }}">
                        @csrf
                        <input type="hidden" name="judul" value="{{ $topik }}">
                        <input type="hidden" name="konten" value="{{ $hasil }}">
                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-4 rounded-md hover:bg-blue-700 shadow-lg">
                            ?? Konfirmasi & Upload ke Materi Siswa (PDF)
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

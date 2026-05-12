<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('? Pembuat Soal Cerdas Berbasis AI') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-emerald-500">
                <div class="p-6 text-gray-900">
                    
                    <form method="POST" action="{{ url('/guru/generate') }}" class="mb-6">
                        @csrf
                        <div class="mb-4">
                            <label for="topik" class="block text-sm font-medium text-gray-700">Topik Materi (Contoh: Daur ulang sampah organik)</label>
                            <input type="text" name="topik" id="topik" value="{{ $topik ?? '' }}" required
                                class="mt-2 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm"
                                placeholder="Ketik topik materi di sini...">
                        </div>
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-3 bg-emerald-600 border border-transparent rounded-md font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition ease-in-out duration-150">
                            ? Generate Soal dengan AI
                        </button>
                    </form>

                    @if(!empty($hasil))
                    <div class="mt-8 p-6 bg-emerald-50 border border-emerald-200 rounded-lg">
                        <h3 class="text-lg font-bold text-emerald-800 mb-4">Hasil Generate:</h3>
                        <div class="prose max-w-none text-gray-800 whitespace-pre-line">
                            {{ $hasil }}
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>

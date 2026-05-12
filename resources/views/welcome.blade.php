<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoLearn4 - AI Soal Generator</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-emerald-50 min-h-screen p-8">
    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-extrabold text-emerald-700">🌿 EcoLearn4</h1>
            <p class="text-gray-600 mt-2 font-medium">Pembuat Soal Cerdas Berbasis AI</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 border-t-4 border-emerald-500">
            <form action="/generate" method="POST">
                @csrf
                <label class="block text-gray-700 font-bold mb-2" for="topik">
                    Topik Materi (Contoh: Daur ulang sampah organik)
                </label>
                <input type="text" name="topik" id="topik" required value="{{ $topik ?? '' }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 mb-4 outline-none transition"
                    placeholder="Ketik topik materi di sini...">
                <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200">
                    ✨ Generate Soal dengan AI
                </button>
            </form>
        </div>

        @if(isset($hasil))
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-emerald-500">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Hasil Generate:</h2>
            <div class="bg-emerald-50/50 p-5 rounded-lg border border-emerald-100 text-gray-800 whitespace-pre-wrap font-sans leading-relaxed">
                {{ $hasil }}
            </div>
        </div>
        @endif
    </div>
</body>
</html>

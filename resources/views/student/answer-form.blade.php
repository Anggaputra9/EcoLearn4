<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800">Kerjakan Soal</h2>
        <p class="text-sm text-slate-500">{{ $material->title }}</p>
    </x-slot>

    <div class="space-y-6 max-w-3xl">
        @if ($errors->any())
            <div class="glass border-rose-200/60 bg-rose-50/60 px-4 py-3 text-rose-700 text-sm">
                @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
            </div>
        @endif

        <div class="glass p-6">
            <p class="text-xs uppercase font-semibold text-emerald-700 tracking-wider">Soal</p>
            <p class="mt-1 text-slate-800 leading-relaxed">{{ $question->prompt_text }}</p>
            @if($question->rubric)
                <p class="mt-3 text-xs text-slate-500"><span class="font-semibold">Rubrik:</span> {{ $question->rubric }}</p>
            @endif
        </div>

        <form method="POST" action="{{ route('student.questions.submit', $question) }}" class="glass p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tulis Esai Anda (min. 20 karakter)</label>
                <textarea name="answer_text" rows="14" required minlength="20" class="input-glass leading-relaxed">{{ old('answer_text', $existing->answer_text ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Jawaban Anda akan dikoreksi otomatis oleh AI.</p>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('student.materials.show', $material) }}" class="btn-secondary">Batal</a>
                <button class="btn-primary"><x-icon name="rocket" class="w-4 h-4"/> Kirim & Koreksi AI</button>
            </div>
        </form>
    </div>
</x-app-layout>

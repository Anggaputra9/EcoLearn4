<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800">Hasil Koreksi</h2>
        <p class="text-sm text-slate-500">{{ $submission->question->material->title }}</p>
    </x-slot>

    <div class="space-y-6 max-w-3xl">
        <div class="glass p-6">
            <p class="text-xs uppercase font-semibold text-emerald-700 tracking-wider">Soal</p>
            <p class="mt-1 text-slate-800 leading-relaxed">{{ $submission->question->prompt_text }}</p>
        </div>

        <div class="glass p-6">
            <h3 class="font-semibold text-slate-800 mb-2">Jawaban Anda</h3>
            <div class="rounded-xl bg-white/50 border border-white/60 p-4 whitespace-pre-wrap text-slate-700 leading-relaxed">{{ $submission->answer_text }}</div>
        </div>

        @if($submission->status === 'graded')
            <div class="glass-strong bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-6 border-emerald-300/40">
                <p class="text-sm opacity-90">Skor</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-6xl font-extrabold">{{ $submission->score }}</span>
                    <span class="text-xl opacity-80">/ 100</span>
                </div>
            </div>
            <div class="glass p-6">
                <h3 class="font-semibold text-slate-800 mb-2">Umpan Balik AI</h3>
                <p class="text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $submission->feedback }}</p>
                <p class="mt-3 text-xs text-slate-400">Dikoreksi {{ $submission->graded_at->diffForHumans() }}</p>
            </div>
        @elseif($submission->status === 'failed')
            <div class="glass border-rose-200/60 bg-rose-50/60 p-6 text-rose-700">
                <p class="font-semibold">Koreksi otomatis gagal</p>
                <p class="text-sm mt-1">{{ $submission->feedback }}</p>
            </div>
        @else
            <div class="glass border-amber-200/60 bg-amber-50/60 p-6 text-amber-700">
                <p>Jawaban sedang menunggu dikoreksi.</p>
            </div>
        @endif

        <div>
            <a href="{{ route('student.materials.show', $submission->question->material) }}" class="btn-secondary">
                <x-icon name="arrow-right" class="w-4 h-4 rotate-180"/> Kembali ke materi
            </a>
        </div>
    </div>
</x-app-layout>

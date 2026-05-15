<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Persiapan Ujian</h2>
        <p class="text-sm text-slate-500">{{ $exam->material->title }}</p>
    </x-slot>

    @php
        $essayCount = $exam->material->questions->where('type', 'essay')->count();
        $mcqCount = $exam->material->questions->where('type', 'mcq')->count();
    @endphp

    <div class="max-w-3xl space-y-6">
        <div class="glass p-6">
            <div class="flex items-center gap-2 mb-2 flex-wrap">
                <span class="badge badge-emerald">{{ $exam->material->level }}</span>
                <span class="text-xs text-slate-500"><x-icon name="clock" class="w-3 h-3 inline"/> {{ $exam->duration_minutes ?: '∞' }} menit</span>
                <span class="text-xs text-slate-500">· {{ $exam->material->questions->count() }} soal</span>
                @if($mcqCount > 0)
                    <span class="badge badge-violet text-[10px]">{{ $mcqCount }} Pilihan Ganda</span>
                @endif
                @if($essayCount > 0)
                    <span class="badge badge-emerald text-[10px]">{{ $essayCount }} Esai</span>
                @endif
            </div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ $exam->title }}</h1>
            @if($exam->description)
                <p class="mt-2 text-slate-600 dark:text-slate-300 leading-relaxed">{{ $exam->description }}</p>
            @endif
        </div>

        <div class="glass p-6">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-3">
                <x-icon name="shield" class="w-5 h-5 text-emerald-600"/> Aturan Ujian
            </h3>
            <ul class="text-sm text-slate-700 dark:text-slate-300 space-y-2 list-disc pl-5">
                <li>Ujian akan dibuka di <strong>ruang ujian khusus</strong> tanpa sidebar dan tombol kembali ke dashboard.</li>
                @if($exam->prevent_tab_switch)
                    <li>Dilarang pindah tab. @if($exam->max_tab_switch === 0) Pindah tab pertama langsung mendiskualifikasi. @else Maksimal {{ $exam->max_tab_switch }} kali; lebih dari itu otomatis didiskualifikasi. @endif</li>
                @endif
                @if($exam->prevent_copy_paste) <li>Copy-paste dinonaktifkan.</li> @endif
                @if($exam->prevent_right_click) <li>Klik kanan dinonaktifkan.</li> @endif
                @if($exam->fullscreen_required) <li>Layar akan otomatis masuk fullscreen. Mohon tetap dalam fullscreen.</li> @endif
                @if($exam->shuffle_questions) <li>Urutan soal akan diacak.</li> @endif
                <li>Jawaban tersimpan otomatis setiap beberapa detik.</li>
                <li><strong>Skor akhir dihitung secara akumulatif</strong> dari seluruh soal — diberikan setelah Anda menyelesaikan semua jawaban.</li>
            </ul>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" class="btn-primary text-lg py-3 px-6 w-full sm:w-auto"
                    @click="$dispatch('open-modal', 'exam-start')">
                <x-icon name="play" class="w-5 h-5"/> Mulai Ujian Sekarang
            </button>
            <a href="{{ route('student.materials.show', $exam->material) }}" class="btn-secondary">
                <x-icon name="arrow-right" class="w-4 h-4 rotate-180"/> Batal
            </a>
        </div>
    </div>

    <x-confirm-modal
        name="exam-start"
        title="Konfirmasi Mulai Ujian"
        tone="primary"
        icon="play"
        confirm-text="Ya, Mulai Sekarang"
        :action="route('student.exams.start', $exam)"
        method="POST"
        :message="'Anda akan masuk ke ruang ujian dan timer berjalan otomatis. Pelanggaran aturan dapat menyebabkan diskualifikasi. Pastikan koneksi stabil dan waktu '.($exam->duration_minutes ?: '∞').' menit cukup untuk Anda menyelesaikan semua soal.'" />
</x-app-layout>

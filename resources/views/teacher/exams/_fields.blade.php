@php
    /** @var \App\Models\Exam|null $exam */
    $defaults = [
        'title' => '', 'description' => '',
        'duration_minutes' => 60, 'starts_at' => null, 'ends_at' => null,
        'prevent_tab_switch' => true, 'max_tab_switch' => 0,
        'prevent_copy_paste' => true, 'prevent_right_click' => true,
        'fullscreen_required' => false, 'shuffle_questions' => false,
        'grading_mode' => 'auto_ai',
        'show_result_after_submit' => true, 'show_leaderboard' => false, 'allow_review_answer' => true,
    ];
    $v = $exam ? $exam->only(array_keys($defaults)) : $defaults;
@endphp

<div class="grid sm:grid-cols-2 gap-3">
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium mb-1">Judul Ujian</label>
        <input name="title" required maxlength="255" value="{{ $v['title'] }}" class="input-glass">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium mb-1">Deskripsi (opsional)</label>
        <textarea name="description" rows="2" class="input-glass">{{ $v['description'] }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Durasi (menit, 0 = tanpa batas)</label>
        <input type="number" name="duration_minutes" min="0" max="600" value="{{ $v['duration_minutes'] }}" required class="input-glass">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Mode Koreksi</label>
        <select name="grading_mode" class="input-glass">
            <option value="auto_ai"  @selected($v['grading_mode'] === 'auto_ai')>Otomatis AI</option>
            <option value="manual"   @selected($v['grading_mode'] === 'manual')>Manual oleh Guru</option>
            <option value="hybrid"   @selected($v['grading_mode'] === 'hybrid')>Hybrid (AI + revisi guru)</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Mulai (opsional)</label>
        <input type="datetime-local" name="starts_at" value="{{ optional($v['starts_at'])->format('Y-m-d\TH:i') }}" class="input-glass">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Berakhir (opsional)</label>
        <input type="datetime-local" name="ends_at" value="{{ optional($v['ends_at'])->format('Y-m-d\TH:i') }}" class="input-glass">
    </div>
</div>

<div class="rounded-xl border border-white/40 dark:border-white/10 p-4 bg-white/40 dark:bg-slate-800/30">
    <div class="flex items-center gap-2 mb-3">
        <x-icon name="lock" class="w-5 h-5 text-emerald-600"/>
        <p class="font-semibold text-slate-800 dark:text-slate-100">Pengaturan Anti-Kecurangan</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-3" x-data="{ tab: {{ $v['prevent_tab_switch'] ? 'true' : 'false' }} }">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="prevent_tab_switch" value="0">
            <input type="checkbox" name="prevent_tab_switch" value="1" x-model="tab" class="rounded">
            <span>Larang pindah tab</span>
        </label>
        <div class="flex items-center gap-2">
            <label class="text-sm">Maks pindah tab (0 = langsung gugur):</label>
            <input type="number" name="max_tab_switch" min="0" max="50" value="{{ $v['max_tab_switch'] }}" :disabled="!tab" class="input-glass w-24">
        </div>

        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="prevent_copy_paste" value="0">
            <input type="checkbox" name="prevent_copy_paste" value="1" {{ $v['prevent_copy_paste'] ? 'checked' : '' }} class="rounded">
            <span>Larang copy & paste</span>
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="prevent_right_click" value="0">
            <input type="checkbox" name="prevent_right_click" value="1" {{ $v['prevent_right_click'] ? 'checked' : '' }} class="rounded">
            <span>Larang klik kanan</span>
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="fullscreen_required" value="0">
            <input type="checkbox" name="fullscreen_required" value="1" {{ $v['fullscreen_required'] ? 'checked' : '' }} class="rounded">
            <span>Wajib mode fullscreen</span>
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="shuffle_questions" value="0">
            <input type="checkbox" name="shuffle_questions" value="1" {{ $v['shuffle_questions'] ? 'checked' : '' }} class="rounded">
            <span>Acak urutan soal</span>
        </label>
    </div>
</div>

<div class="rounded-xl border border-white/40 dark:border-white/10 p-4 bg-white/40 dark:bg-slate-800/30">
    <div class="flex items-center gap-2 mb-3">
        <x-icon name="eye" class="w-5 h-5 text-emerald-600"/>
        <p class="font-semibold text-slate-800 dark:text-slate-100">Visibilitas Hasil</p>
    </div>
    <div class="grid sm:grid-cols-2 gap-3">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="show_result_after_submit" value="0">
            <input type="checkbox" name="show_result_after_submit" value="1" {{ $v['show_result_after_submit'] ? 'checked' : '' }} class="rounded">
            <span>Tampilkan hasil ke siswa setelah submit</span>
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="show_leaderboard" value="0">
            <input type="checkbox" name="show_leaderboard" value="1" {{ $v['show_leaderboard'] ? 'checked' : '' }} class="rounded">
            <span>Tampilkan leaderboard</span>
        </label>
        <label class="inline-flex items-center gap-2 text-sm sm:col-span-2">
            <input type="hidden" name="allow_review_answer" value="0">
            <input type="checkbox" name="allow_review_answer" value="1" {{ $v['allow_review_answer'] ? 'checked' : '' }} class="rounded">
            <span>Izinkan siswa melihat ulang jawaban & feedback</span>
        </label>
    </div>
</div>

@php
    $isEdit = (bool) $k;
    $allModels = collect($providers)->mapWithKeys(fn ($n, $p) => [$p => $aiService->staticModelList($p)])->all();
    $currentProvider = $isEdit ? $k->provider : 'gemini';
    $currentModel    = $isEdit ? (string) $k->model : '';
@endphp

<div x-data="{ provider: '{{ $currentProvider }}', model: @js($currentModel) }" class="space-y-3">
    <div class="grid sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Label</label>
            <input name="label" value="{{ old('label', $isEdit ? $k->label : '') }}" required maxlength="120"
                   placeholder="Contoh: Gemini Pribadi" class="input-glass">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Provider</label>
            <select name="provider" x-model="provider" class="input-glass">
                @foreach($providers as $p => $name)
                    <option value="{{ $p }}" @selected(($isEdit ? $k->provider : 'gemini') === $p)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
            Model
            <span class="text-xs font-normal text-slate-400">(opsional · boleh ketik kustom)</span>
        </label>

        {{-- Datalist per-provider, sehingga pengguna bisa pilih dari saran ATAU ketik bebas
             (mis. nama model preview/experimental yang belum ada di daftar). --}}
        @foreach($allModels as $p => $list)
            <datalist id="aikey-models-{{ $p }}-{{ $isEdit ? $k->id : 'new' }}">
                @foreach($list as $m)
                    <option value="{{ $m }}"></option>
                @endforeach
            </datalist>
        @endforeach

        <input name="model"
               x-model="model"
               :list="'aikey-models-' + provider + '-{{ $isEdit ? $k->id : 'new' }}'"
               autocomplete="off"
               placeholder="Kosongkan = pakai default provider"
               class="input-glass font-mono text-sm">

        <p class="mt-1 text-[11px] text-slate-500">
            Untuk Gemini misalnya: <span class="font-mono">gemini-3.0-pro</span>, <span class="font-mono">gemini-2.5-flash</span>, atau model preview kustom.
        </p>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
            API Key {{ $isEdit ? '(kosongkan jika tidak diubah)' : '' }}
        </label>
        <input name="api_key" type="password" autocomplete="off" {{ $isEdit ? '' : 'required' }} class="input-glass"
               placeholder="{{ $isEdit ? '•••• tersimpan' : 'Tempel API key di sini' }}">
    </div>

    <div class="grid sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Prioritas</label>
            <input type="number" name="priority" value="{{ old('priority', $isEdit ? $k->priority : 0) }}" min="0" max="9999" class="input-glass">
            <p class="text-[11px] text-slate-500 mt-1">Angka kecil = duluan dipakai</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Limit Kuota</label>
            <input type="number" name="quota_limit" value="{{ old('quota_limit', $isEdit ? $k->quota_limit : '') }}" min="0" placeholder="kosong = tak terbatas" class="input-glass">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Reset Kuota</label>
            <select name="quota_reset_period" class="input-glass">
                @foreach(['none' => 'Tidak otomatis', 'daily' => 'Harian', 'monthly' => 'Bulanan'] as $v => $lbl)
                    <option value="{{ $v }}" @selected(($isEdit ? $k->quota_reset_period : 'monthly') === $v)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200 mt-1">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="rounded border-white/60"
               {{ ($isEdit ? $k->is_active : true) ? 'checked' : '' }}>
        Aktif
    </label>
</div>

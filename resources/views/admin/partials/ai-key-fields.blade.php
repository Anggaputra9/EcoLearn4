@php
    $isEdit = (bool) $k;
    $currentProvider = $isEdit ? $k->provider : 'gemini';
    $currentModel    = $isEdit ? (string) $k->model : '';
    $listId          = 'aikey-models-'.($isEdit ? $k->id : 'new');
@endphp

<div x-data="aiKeyForm({ provider: '{{ $currentProvider }}', model: @js($currentModel) })" x-init="loadModels(false)" class="space-y-3">
    <div class="grid sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Label</label>
            <input name="label" value="{{ old('label', $isEdit ? $k->label : '') }}" required maxlength="120"
                   placeholder="Contoh: Gemini Pribadi" class="input-glass">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Provider</label>
            <select name="provider" x-model="provider" @change="loadModels(false)" class="input-glass">
                @foreach($providers as $p => $name)
                    <option value="{{ $p }}" @selected(($isEdit ? $k->provider : 'gemini') === $p)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <div class="flex items-end justify-between gap-2 mb-1">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                Model
                <span class="text-xs font-normal text-slate-400 hidden sm:inline">(opsional · boleh ketik kustom)</span>
            </label>
            <button type="button" class="btn-ghost text-xs px-2 py-1" @click="loadModels(true)" :disabled="loading">
                <x-icon name="history" class="w-3.5 h-3.5"/>
                <span x-text="loading ? 'Memuat…' : 'Muat ulang'"></span>
            </button>
        </div>

        <datalist id="{{ $listId }}">
            <template x-for="m in models" :key="m">
                <option :value="m"></option>
            </template>
        </datalist>

        <input name="model"
               x-model="model"
               list="{{ $listId }}"
               autocomplete="off"
               placeholder="Kosongkan = pakai default provider"
               class="input-glass font-mono text-sm">

        <p class="mt-1 text-[11px] text-slate-500">
            <span x-show="!loading && models.length > 0">
                <span class="text-emerald-600">●</span>
                <span x-text="models.length"></span> model live dari endpoint provider.
            </span>
            <span x-show="loading">Memuat daftar model…</span>
            <span x-show="error" class="text-rose-600" x-text="error"></span>
        </p>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
            API Key {{ $isEdit ? '(kosongkan jika tidak diubah)' : '' }}
        </label>
        <input name="api_key" type="password" autocomplete="off" {{ $isEdit ? '' : 'required' }} class="input-glass"
               placeholder="{{ $isEdit ? '•••• tersimpan' : 'Tempel API key di sini' }}">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Prioritas</label>
        <input type="number" name="priority" value="{{ old('priority', $isEdit ? $k->priority : '') }}"
               min="0" max="9999" placeholder="kosong = otomatis (paling akhir)" class="input-glass sm:max-w-xs">
        <p class="text-[11px] text-slate-500 mt-1">
            Angka kecil = duluan dipakai. Kuota & periode reset diatur otomatis sesuai tier free provider.
        </p>
    </div>

    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200 mt-1">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="rounded border-white/60"
               {{ ($isEdit ? $k->is_active : true) ? 'checked' : '' }}>
        Aktif
    </label>
</div>

@once
<script>
    function aiKeyForm({ provider, model }) {
        return {
            provider, model,
            models: [], loading: false, error: '',
            async loadModels(forceRefresh = false) {
                this.loading = true; this.error = '';
                try {
                    const url = '{{ url('/admin/ai/models') }}?provider=' + encodeURIComponent(this.provider) + (forceRefresh ? '&refresh=1' : '');
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message || 'Gagal memuat model.');
                    this.models = data.models || [];
                } catch (e) {
                    this.models = [];
                    this.error = e.message;
                } finally {
                    this.loading = false;
                }
            },
        }
    }
</script>
@endonce

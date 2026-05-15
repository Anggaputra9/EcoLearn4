@php $cl = $cl ?? null; @endphp
<div class="grid sm:grid-cols-3 gap-3">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Versi</label>
        <input name="version" required value="{{ $cl?->version }}" class="input-glass" placeholder="0.2.0">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Jenis</label>
        <select name="kind" class="input-glass">
            @foreach(['major','minor','patch','hotfix'] as $k)
                <option value="{{ $k }}" @selected(($cl?->kind ?? 'minor') === $k)>{{ ucfirst($k) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Rilis</label>
        <input type="date" name="released_at" required value="{{ $cl?->released_at?->format('Y-m-d') ?? now()->toDateString() }}" class="input-glass">
    </div>
</div>
<div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Judul</label>
    <input name="title" required value="{{ $cl?->title }}" class="input-glass">
</div>
<div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Catatan Perubahan</label>
    <textarea name="notes" rows="6" required class="input-glass">{{ $cl?->notes }}</textarea>
</div>

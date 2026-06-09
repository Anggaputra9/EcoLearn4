@props([
    'out',
    'slidesRoute',
    'material',
])

@if(!empty($out['html_path']))
    <div class="space-y-4">
        <p class="text-sm text-slate-600 dark:text-slate-300">{{ $out['content'] }}</p>
        <a href="{{ $slidesRoute }}" target="_blank" rel="noopener"
           class="btn-primary inline-flex items-center gap-2">
            <x-icon name="monitor" class="w-4 h-4"/> Tampilkan PPT
        </a>
        <p class="text-xs text-slate-400">
            Presentasi terbuka di tab baru. Gunakan tombol panah atau keyboard ← → untuk navigasi slide.
        </p>
    </div>
@else
    <article class="whitespace-pre-wrap text-slate-800 dark:text-slate-200 leading-relaxed">{{ $out['content'] }}</article>
@endif
@props([
    'out',
    'slidesRoute',
    'material',
])

@if(!empty($out['html_path']))
    @php
        $embedUrl = $slidesRoute.(str_contains($slidesRoute, '?') ? '&' : '?').'embed=1';
    @endphp
    <div class="space-y-3">
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ $out['content'] }}</p>
            <a href="{{ $slidesRoute }}" target="_blank" rel="noopener"
               class="btn-secondary text-xs py-1.5 px-3">
                <x-icon name="monitor" class="w-3.5 h-3.5"/> Buka layar penuh
            </a>
        </div>
        {{-- Preview 16:9 — lebar penuh area konten, tanpa scroll di dalam iframe --}}
        <div class="slides-preview-frame w-full">
            <div class="relative w-full aspect-video rounded-2xl overflow-hidden border border-white/60 dark:border-white/10 bg-slate-900 shadow-inner">
                <iframe
                    src="{{ $embedUrl }}"
                    title="Presentasi {{ $material->title }}"
                    class="absolute inset-0 w-full h-full border-0 bg-white"
                    loading="lazy"
                    scrolling="no"
                    sandbox="allow-scripts allow-same-origin"
                ></iframe>
            </div>
        </div>
        <p class="text-xs text-slate-400">
            Pratinjau 16:9. Gunakan tombol panah atau keyboard ← → untuk navigasi slide.
        </p>
    </div>
@else
    <article class="whitespace-pre-wrap text-slate-800 dark:text-slate-200 leading-relaxed">{{ $out['content'] }}</article>
@endif
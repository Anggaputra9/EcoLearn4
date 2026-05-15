@props([
    'name',
    'title' => 'Dialog',
    'maxWidth' => '2xl',
])

@php
    $sizes = [
        'sm'  => 'sm:max-w-sm',
        'md'  => 'sm:max-w-md',
        'lg'  => 'sm:max-w-lg',
        'xl'  => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
    ];
    $size = $sizes[$maxWidth] ?? $sizes['2xl'];
@endphp

{{--
    Modal di-teleport ke <body> + z-index sangat tinggi (z-[2000]) supaya
    SELALU menutup header sticky / sidebar / konten apa pun. Tanpa teleport,
    `position: fixed` di dalam modal akan terjebak di containing block
    ancestor yang punya backdrop-filter/transform.
--}}
<template x-teleport="body">
    <div x-data="{
            show: false,
            lock() { document.documentElement.classList.add('overflow-hidden'); document.body.classList.add('overflow-hidden'); },
            unlock() { document.documentElement.classList.remove('overflow-hidden'); document.body.classList.remove('overflow-hidden'); }
         }"
         x-on:open-modal.window="if ($event.detail === '{{ $name }}') { show = true; lock(); }"
         x-on:close-modal.window="if ($event.detail === '{{ $name }}') { show = false; unlock(); }"
         x-on:keydown.escape.window="show = false; unlock();"
         x-show="show"
         x-cloak
         class="fixed inset-0"
         style="z-index: 2000; display: none;">

        {{-- Overlay full-bleed: blur tebal ke seluruh viewport (sidebar, header, konten ikut blur) --}}
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80"
             style="z-index: 2000; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);"
             x-show="show"
             x-transition.opacity
             @click="show = false; unlock();"></div>

        {{-- Wrapper dialog: grid place-items-center supaya selalu di tengah viewport secara vertikal & horizontal --}}
        <div class="fixed inset-0 overflow-y-auto" style="z-index: 2010;" @click.self="show = false; unlock();">
            <div class="min-h-full grid place-items-center p-3 sm:p-4">
                <div x-show="show"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     @click.stop
                     class="glass-strong w-full {{ $size }} max-h-[calc(100vh-1.5rem)] overflow-y-auto p-5 sm:p-6 relative">

                    <div class="flex items-start justify-between gap-4 mb-4">
                        <h3 class="text-base sm:text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h3>
                        <button type="button"
                                class="btn-ghost p-1.5 -m-1.5 shrink-0"
                                @click="show = false; unlock();">
                            <x-icon name="close" class="w-5 h-5"/>
                        </button>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</template>

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
    Modal di-teleport ke <body> supaya tidak terjebak di containing block
    yang punya backdrop-filter / transform (mis. card .glass). Tanpa teleport,
    `position: fixed` akan dihitung relatif ke ancestor tersebut sehingga modal
    "masuk ke dalam card".
--}}
<template x-teleport="body">
    <div x-data="{ show: false }"
         x-on:open-modal.window="if ($event.detail === '{{ $name }}') { show = true; document.body.classList.add('overflow-hidden'); }"
         x-on:close-modal.window="if ($event.detail === '{{ $name }}') { show = false; document.body.classList.remove('overflow-hidden'); }"
         x-on:keydown.escape.window="show = false; document.body.classList.remove('overflow-hidden');"
         x-show="show"
         x-cloak
         class="fixed inset-0 z-[100] overflow-y-auto"
         style="display: none;">

        {{-- Overlay --}}
        <div class="fixed inset-0 bg-slate-900/50 dark:bg-slate-950/75 backdrop-blur-sm"
             x-show="show"
             x-transition.opacity
             @click="show = false; document.body.classList.remove('overflow-hidden');"></div>

        {{-- Wrapper untuk centering. min-h-full + items-center bekerja konsisten di mobile. --}}
        <div class="relative min-h-full flex items-center justify-center p-3 sm:p-4">
            <div x-show="show"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 @click.stop
                 class="glass-strong w-full {{ $size }} max-h-[calc(100vh-1.5rem)] overflow-y-auto p-5 sm:p-6 relative">

                <div class="flex items-start justify-between gap-4 mb-4 sticky top-0 -mt-1 pt-1 bg-transparent">
                    <h3 class="text-base sm:text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h3>
                    <button type="button"
                            class="btn-ghost p-1.5 -m-1.5 shrink-0"
                            @click="show = false; document.body.classList.remove('overflow-hidden');">
                        <x-icon name="close" class="w-5 h-5"/>
                    </button>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</template>

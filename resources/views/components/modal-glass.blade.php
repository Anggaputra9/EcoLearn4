@props([
    'name',
    'title' => 'Dialog',
    'maxWidth' => '2xl',
])

@php
    $sizes = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
    ];
    $size = $sizes[$maxWidth] ?? $sizes['2xl'];
@endphp

<div x-data="{ show: false }"
     x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
     x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
     x-on:keydown.escape.window="show = false"
     x-show="show"
     x-cloak
     style="display: none;">

    <div class="fixed inset-0 z-50 overflow-y-auto" x-show="show">
        <div class="fixed inset-0 bg-slate-900/40 dark:bg-slate-950/70 backdrop-blur-sm"
             x-show="show" x-transition.opacity
             @click="show = false"></div>

        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="show"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.stop
                 class="glass-strong w-full {{ $size }} p-6 relative">

                <div class="flex items-start justify-between gap-4 mb-4">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h3>
                    <button type="button" @click="show = false" class="btn-ghost p-1.5 -m-1.5">
                        <x-icon name="close" class="w-5 h-5"/>
                    </button>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</div>

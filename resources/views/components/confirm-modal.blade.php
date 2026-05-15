@props([
    'name',                          // unique modal id
    'title' => 'Konfirmasi',
    'message' => 'Yakin ingin melanjutkan?',
    'confirmText' => 'Lanjutkan',
    'cancelText' => 'Batal',
    'tone' => 'danger',              // danger | primary | warning
    'icon' => 'shield',              // x-icon name
    'action' => null,                // route URL (form submit). Null = pakai mode JS button (slot=button)
    'method' => 'POST',              // POST | DELETE | PUT | PATCH | GET
    'extraFields' => [],              // [name => value] untuk hidden input tambahan
])

@php
    $btnClass = match ($tone) {
        'danger'  => 'btn-danger',
        'warning' => 'btn-secondary text-amber-700 dark:text-amber-300',
        default   => 'btn-primary',
    };
    $iconColor = match ($tone) {
        'danger'  => 'text-rose-600',
        'warning' => 'text-amber-500',
        default   => 'text-emerald-600',
    };
    $iconBg = match ($tone) {
        'danger'  => 'bg-rose-100 dark:bg-rose-900/40',
        'warning' => 'bg-amber-100 dark:bg-amber-900/40',
        default   => 'bg-emerald-100 dark:bg-emerald-900/40',
    };
    $methodUpper = strtoupper($method);
@endphp

<x-modal-glass :name="$name" :title="$title" max-width="md">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 grid place-items-center rounded-full {{ $iconBg }} shrink-0">
            <x-icon :name="$icon" class="w-5 h-5 {{ $iconColor }}"/>
        </div>
        <div class="flex-1">
            <p class="text-slate-700 dark:text-slate-200 leading-relaxed">{!! $message !!}</p>
            {{ $slot }}
        </div>
    </div>

    @if($action)
        <form method="{{ in_array($methodUpper, ['GET','POST']) ? $methodUpper : 'POST' }}" action="{{ $action }}" class="flex justify-end gap-2 mt-6">
            @if($methodUpper !== 'GET') @csrf @endif
            @if(! in_array($methodUpper, ['GET','POST'])) @method($methodUpper) @endif
            @foreach($extraFields as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <button type="button" class="btn-secondary" @click="$dispatch('close-modal', '{{ $name }}')">{{ $cancelText }}</button>
            <button class="{{ $btnClass }}">
                <x-icon :name="$icon" class="w-4 h-4"/> {{ $confirmText }}
            </button>
        </form>
    @else
        {{-- Caller bisa pakai slot 'footer' untuk render tombol custom --}}
        <div class="flex justify-end gap-2 mt-6">
            {{ $footer ?? '' }}
        </div>
    @endif
</x-modal-glass>

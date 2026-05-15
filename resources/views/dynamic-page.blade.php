<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800">{{ $menu->nama_menu }}</h2>
    </x-slot>

    <div class="glass p-8">
        @if($menu->konten)
            <article class="whitespace-pre-wrap text-slate-800 leading-relaxed">{{ $menu->konten }}</article>
        @else
            <div class="text-center py-10">
                <div class="mx-auto w-12 h-12 rounded-xl bg-emerald-50 grid place-items-center text-emerald-600 mb-3">
                    <x-icon name="doc-text" class="w-6 h-6"/>
                </div>
                <p class="text-slate-700 font-medium">Halaman Kosong</p>
                <p class="text-sm text-slate-500 mt-1">Admin belum menambahkan konten untuk menu ini.</p>
            </div>
        @endif
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Selamat Datang di Eko-Scribe</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-8">
                <p class="text-gray-700 leading-relaxed">
                    Halo, <span class="font-semibold text-emerald-700">{{ auth()->user()->name }}</span>.
                    Pilih menu di bagian atas untuk mulai menggunakan platform pembelajaran esai ekoteologi otomatis.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Konfigurasi AI</h2>
        <p class="text-sm text-slate-500">Pilih provider & model AI default. Kelola API Key di menu <a href="{{ url('/admin/ai-keys') }}" class="text-emerald-600 hover:underline">API Key Pool</a>.</p>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-6" x-data="{ provider: '{{ $provider }}' }">
        <div class="lg:col-span-2 glass p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 grid place-items-center text-white">
                    <x-icon name="sparkles" class="w-5 h-5"/>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Provider AI Default</h3>
                    <p class="text-xs text-slate-500">Akan digunakan untuk generate materi, soal, dan koreksi otomatis.</p>
                </div>
            </div>

            <form method="POST" action="{{ url('/admin/settings') }}" class="space-y-4">
                @csrf @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Provider</label>
                    <select name="provider" x-model="provider" class="input-glass">
                        @foreach($providers as $p => $name)
                            <option value="{{ $p }}" @selected($provider === $p)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Model Default</label>
                    <select name="model" class="input-glass">
                        @foreach($staticLists as $p => $list)
                            @foreach($list as $m)
                                <option value="{{ $m }}" x-show="provider === '{{ $p }}'" @selected($provider === $p && $model === $m)>{{ $m }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Setiap API Key boleh menimpa model ini di halaman API Key Pool.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Simpan</button>
                </div>
            </form>

            <hr class="my-6 border-white/40 dark:border-white/10">
            <form method="POST" action="{{ url('/admin/settings/test') }}">
                @csrf
                <button class="btn-secondary"><x-icon name="rocket" class="w-4 h-4"/> Tes Koneksi AI</button>
            </form>
        </div>

        <div class="glass p-6">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3 flex items-center gap-2">
                <x-icon name="shield" class="w-5 h-5 text-emerald-600"/> Catatan
            </h3>
            <ul class="text-sm text-slate-600 dark:text-slate-300 space-y-2 list-disc pl-5">
                <li>Banyak API key bisa digabung untuk satu provider; sistem otomatis berpindah jika satu kehabisan kuota.</li>
                <li>Atur prioritas key di <a class="text-emerald-600 hover:underline" href="{{ url('/admin/ai-keys') }}">API Key Pool</a>.</li>
                <li>Provider yang didukung: Gemini, OpenAI, Anthropic, OpenRouter, Groq.</li>
            </ul>
        </div>
    </div>
</x-app-layout>

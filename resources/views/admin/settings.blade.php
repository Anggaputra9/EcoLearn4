<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg sm:text-xl font-bold text-slate-800 dark:text-slate-100">Konfigurasi AI</h2>
        <p class="text-xs sm:text-sm text-slate-500">Pilih provider & model AI default. Kelola API Key di menu <a href="{{ url('/admin/ai-keys') }}" class="text-emerald-600 hover:underline">API Key Pool</a>.</p>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-4 sm:gap-6" x-data="{ provider: '{{ $provider }}', model: '{{ $model }}' }">
        <div class="lg:col-span-2 glass p-4 sm:p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 grid place-items-center text-white shrink-0">
                    <x-icon name="sparkles" class="w-5 h-5"/>
                </div>
                <div class="min-w-0">
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
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
                        Model Default
                        <span class="text-xs font-normal text-slate-400">(pilih dari daftar atau ketik nama model kustom)</span>
                    </label>

                    {{-- Datalist per-provider supaya pengguna boleh ketik bebas (mis. gemini-3.0-pro-preview, model OpenRouter custom, dsb). --}}
                    @foreach($staticLists as $p => $list)
                        <datalist id="models-{{ $p }}">
                            @foreach($list as $m)
                                <option value="{{ $m }}"></option>
                            @endforeach
                        </datalist>
                    @endforeach

                    <input name="model"
                           x-model="model"
                           :list="'models-' + provider"
                           required
                           autocomplete="off"
                           placeholder="contoh: gemini-3.0-pro"
                           class="input-glass font-mono text-sm">

                    <p class="mt-1 text-xs text-slate-500">
                        Bebas ketik model lain (preview, experimental, custom OpenRouter, dst). Setiap API Key juga boleh menimpa model ini di halaman API Key Pool.
                    </p>
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

        <div class="glass p-4 sm:p-6">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3 flex items-center gap-2">
                <x-icon name="shield" class="w-5 h-5 text-emerald-600"/> Catatan
            </h3>
            <ul class="text-sm text-slate-600 dark:text-slate-300 space-y-2 list-disc pl-5">
                <li>Banyak API key bisa digabung untuk satu provider; sistem otomatis berpindah jika satu kehabisan kuota.</li>
                <li>Atur prioritas key di <a class="text-emerald-600 hover:underline" href="{{ url('/admin/ai-keys') }}">API Key Pool</a>.</li>
                <li>Model Gemini didukung sampai seri <span class="font-mono">gemini-3.0-*</span>. Anda juga bisa mengetik nama model preview/experimental terbaru langsung di kolom di atas.</li>
                <li>Provider yang didukung: Gemini, OpenAI, Anthropic, OpenRouter, Groq.</li>
            </ul>
        </div>
    </div>
</x-app-layout>

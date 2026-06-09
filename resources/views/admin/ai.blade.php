<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-lg sm:text-xl font-bold text-slate-800 dark:text-slate-100">Konfigurasi AI</h2>
                <p class="text-xs sm:text-sm text-slate-500">Provider, model default, dan API Key Pool dalam satu halaman.</p>
            </div>
        </div>
    </x-slot>

    <div x-data="{ tab: '{{ request('tab', 'general') }}' }" class="space-y-5">
        {{-- Tab switcher --}}
        <div class="glass p-1.5 inline-flex flex-wrap gap-1 text-sm">
            <button type="button" @click="tab='general'"
                    :class="tab==='general' ? 'bg-emerald-500 text-white shadow' : 'text-slate-600 dark:text-slate-300'"
                    class="px-4 py-2 rounded-xl font-medium transition">
                <x-icon name="sparkles" class="w-4 h-4 inline -mt-0.5"/> Umum
            </button>
            <button type="button" @click="tab='custom'"
                    :class="tab==='custom' ? 'bg-emerald-500 text-white shadow' : 'text-slate-600 dark:text-slate-300'"
                    class="px-4 py-2 rounded-xl font-medium transition">
                <x-icon name="puzzle" class="w-4 h-4 inline -mt-0.5"/> Provider Kustom
                <span class="ml-1 text-xs opacity-80">({{ count($customProviders) }})</span>
            </button>
            <button type="button" @click="tab='keys'"
                    :class="tab==='keys' ? 'bg-emerald-500 text-white shadow' : 'text-slate-600 dark:text-slate-300'"
                    class="px-4 py-2 rounded-xl font-medium transition">
                <x-icon name="key" class="w-4 h-4 inline -mt-0.5"/> API Key Pool
                <span class="ml-1 text-xs opacity-80">({{ $keys->count() }})</span>
            </button>
        </div>

        {{-- ============= TAB: UMUM ============= --}}
        <div x-show="tab==='general'" x-cloak class="grid lg:grid-cols-3 gap-4 sm:gap-6"
             x-data="aiGeneral({ provider: '{{ $provider }}', model: '{{ $model }}' })" x-init="initial()">
            <div class="lg:col-span-2 glass p-5 sm:p-7">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 grid place-items-center text-white shrink-0">
                        <x-icon name="sparkles" class="w-5 h-5"/>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Provider AI Default</h3>
                        <p class="text-xs text-slate-500">Akan dipakai untuk generate materi, soal, dan koreksi otomatis.</p>
                    </div>
                </div>

                <form method="POST" action="{{ url('/admin/ai/general') }}" class="space-y-4">
                    @csrf @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Provider</label>
                        <select name="provider" x-model="provider" @change="loadModels()" class="input-glass">
                            @foreach($providers as $p => $name)
                                <option value="{{ $p }}" @selected($provider === $p)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <div class="flex items-end justify-between gap-2 mb-1">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                                Model Default
                                <span class="text-xs font-normal text-slate-400 hidden sm:inline">(pilih dari daftar atau ketik nama model kustom)</span>
                            </label>
                            <button type="button" class="btn-ghost text-xs px-2 py-1" @click="loadModels(true)" :disabled="loading">
                                <x-icon name="history" class="w-3.5 h-3.5"/>
                                <span x-text="loading ? 'Memuat…' : 'Muat ulang'"></span>
                            </button>
                        </div>

                        <datalist id="models-live">
                            <template x-for="m in models" :key="m">
                                <option :value="m"></option>
                            </template>
                        </datalist>

                        <input name="model"
                               x-model="model"
                               list="models-live"
                               required autocomplete="off"
                               placeholder="contoh: gemini-3.0-pro"
                               class="input-glass font-mono text-sm">

                        <p class="mt-1 text-xs text-slate-500">
                            <span x-show="!loading && models.length > 0">
                                <span class="text-emerald-600">●</span>
                                <span x-text="models.length"></span> model dimuat live dari endpoint provider.
                            </span>
                            <span x-show="loading">Memuat daftar model…</span>
                            <span x-show="error" class="text-rose-600" x-text="error"></span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-2">
                        <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Simpan</button>
                    </div>
                </form>

                <hr class="my-6 border-white/40 dark:border-white/10">
                <form method="POST" action="{{ url('/admin/ai/test') }}">
                    @csrf
                    <button class="btn-secondary"><x-icon name="rocket" class="w-4 h-4"/> Tes Koneksi AI</button>
                </form>

                <hr class="my-6 border-white/40 dark:border-white/10">

                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 grid place-items-center text-white shrink-0">
                        <x-icon name="photo" class="w-5 h-5"/>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Gambar Slide PPT (Scraping)</h3>
                        <p class="text-xs text-slate-500">AI bebas desain; jika pakai gambar, sistem scrape dari Google Images lalu unduh permanen ke server.</p>
                    </div>
                    <span class="badge badge-emerald ml-auto">Otomatis</span>
                </div>
                <form method="POST" action="{{ route('admin.ai.scrape-images.test') }}">
                    @csrf
                    <button class="btn-secondary text-sm"><x-icon name="photo" class="w-4 h-4"/> Tes Scrape & Simpan Gambar</button>
                </form>
            </div>


            <div class="glass p-4 sm:p-6">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3 flex items-center gap-2">
                    <x-icon name="shield" class="w-5 h-5 text-emerald-600"/> Catatan
                </h3>
                <ul class="text-sm text-slate-600 dark:text-slate-300 space-y-2 list-disc pl-5">
                    <li>Daftar model di-fetch <span class="font-semibold">live</span> dari endpoint provider (cache 10 menit). Klik <span class="font-medium">Muat ulang</span> untuk paksa refresh.</li>
                    <li>Saat respons <span class="font-mono">429</span> / kuota habis, sistem otomatis tandai key sebagai exhausted dan pindah ke key berikutnya hingga periode reset.</li>
                    <li>Atur prioritas key di tab <span class="font-semibold">API Key Pool</span>. Kuota & periode reset dipasang otomatis sesuai tier free tiap provider.</li>
                    <li>Provider bawaan: Gemini, OpenAI, Anthropic, OpenRouter, Groq, HidePulsa. Tambah provider OpenAI-compatible lain di tab <span class="font-semibold">Provider Kustom</span>.</li>
                    <li>Slide PPT: AI bebas desain. Gambar (jika ada) di-scrape lalu disimpan di <span class="font-mono">storage/material-presentations/…/images/</span>.</li>
                </ul>

            </div>
        </div>

        {{-- ============= TAB: PROVIDER KUSTOM ============= --}}
        <div x-show="tab==='custom'" x-cloak class="space-y-5">
            <div class="grid lg:grid-cols-3 gap-4 sm:gap-6">
                <div class="lg:col-span-2 glass p-5 sm:p-7" x-data="customProviderForm()">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 grid place-items-center text-white shrink-0">
                            <x-icon name="puzzle" class="w-5 h-5"/>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Tambah Provider Kustom</h3>
                            <p class="text-xs text-slate-500">Untuk API OpenAI-compatible (DeepSeek, Together, Ollama gateway, dll).</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ url('/admin/ai/custom-providers') }}" class="space-y-4">
                        @csrf
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Nama Provider</label>
                                <input name="name" required maxlength="80" placeholder="Contoh: DeepSeek" class="input-glass"
                                       x-model="name" @input="syncSlug()">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Slug</label>
                                <input name="slug" maxlength="40" placeholder="custom_deepseek" class="input-glass font-mono text-sm"
                                       x-model="slug">
                                <p class="text-[11px] text-slate-500 mt-1">Harus diawali <span class="font-mono">custom_</span></p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Base URL</label>
                            <input name="base_url" required type="url" placeholder="https://api.deepseek.com/v1" class="input-glass font-mono text-sm"
                                   x-model="baseUrl">
                            <p class="text-[11px] text-slate-500 mt-1">Sistem otomatis pakai <span class="font-mono">/models</span> dan <span class="font-mono">/chat/completions</span>.</p>
                        </div>

                        <details class="text-sm">
                            <summary class="cursor-pointer text-slate-600 dark:text-slate-300 font-medium">Endpoint lanjutan (opsional)</summary>
                            <div class="grid sm:grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Chat URL</label>
                                    <input name="chat_url" type="url" placeholder="https://..." class="input-glass font-mono text-xs" x-model="chatUrl">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Models URL</label>
                                    <input name="models_url" type="url" placeholder="https://..." class="input-glass font-mono text-xs" x-model="modelsUrl">
                                </div>
                            </div>
                        </details>

                        <div class="rounded-xl bg-slate-50/80 dark:bg-slate-800/40 border border-white/60 dark:border-white/10 p-3">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">API Key (untuk tes fetch model)</label>
                            <input type="password" autocomplete="off" placeholder="Opsional — untuk uji koneksi sebelum simpan" class="input-glass"
                                   x-model="apiKey">
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <button type="button" class="btn-secondary text-sm" @click="previewModels()" :disabled="previewLoading">
                                    <x-icon name="rocket" class="w-4 h-4"/>
                                    <span x-text="previewLoading ? 'Mengambil model…' : 'Tes Fetch Model'"></span>
                                </button>
                                <span class="text-xs text-emerald-600" x-show="previewCount > 0" x-text="previewCount + ' model ditemukan'"></span>
                                <span class="text-xs text-rose-600" x-show="previewError" x-text="previewError"></span>
                            </div>
                            <div class="mt-2 max-h-28 overflow-y-auto text-xs font-mono text-slate-600 dark:text-slate-300" x-show="previewModelsList.length">
                                <template x-for="m in previewModelsList" :key="m">
                                    <div x-text="m"></div>
                                </template>
                            </div>
                        </div>

                        <button class="btn-primary"><x-icon name="plus" class="w-4 h-4"/> Simpan Provider</button>
                    </form>
                </div>

                <div class="glass p-4 sm:p-6">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3">Cara kerja</h3>
                    <ul class="text-sm text-slate-600 dark:text-slate-300 space-y-2 list-disc pl-5">
                        <li>Provider kustom diasumsikan <span class="font-semibold">OpenAI-compatible</span>.</li>
                        <li>Setelah disimpan, tambahkan API key di tab <span class="font-semibold">API Key Pool</span>.</li>
                        <li>Daftar model di-fetch otomatis dari endpoint <span class="font-mono">/v1/models</span> saat memilih provider.</li>
                        <li>Gunakan tombol <span class="font-semibold">Tes Fetch Model</span> untuk validasi sebelum menyimpan.</li>
                    </ul>
                </div>
            </div>

            @if(empty($customProviders))
                <div class="glass p-8 text-center text-slate-500">Belum ada provider kustom.</div>
            @else
                <div class="space-y-3">
                    @foreach($customProviders as $slug => $cfg)
                        @php $urls = $aiService->customProviderUrls($slug); @endphp
                        <div class="glass p-4 sm:p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">{{ $cfg['name'] ?? $slug }}</h3>
                                    <p class="text-xs font-mono text-slate-500">{{ $slug }}</p>
                                    <p class="text-xs text-slate-500 mt-1 break-all">{{ $cfg['base_url'] ?? '' }}</p>
                                    @if($urls)
                                        <p class="text-[11px] text-slate-400 mt-1 break-all">models: {{ $urls['models'] }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" class="btn-ghost p-2" title="Edit" @click="$dispatch('open-modal', 'custom-edit-{{ $slug }}')">
                                        <x-icon name="pencil" class="w-4 h-4"/>
                                    </button>
                                    <button type="button" class="btn-ghost p-2 text-rose-600" title="Hapus" @click="$dispatch('open-modal', 'custom-del-{{ $slug }}')">
                                        <x-icon name="trash" class="w-4 h-4"/>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <x-modal-glass name="custom-edit-{{ $slug }}" title="Edit Provider Kustom" max-width="lg">
                            <form method="POST" action="{{ url('/admin/ai/custom-providers/'.$slug) }}" class="space-y-3">
                                @csrf @method('PUT')
                                <div>
                                    <label class="block text-sm font-medium mb-1">Nama</label>
                                    <input name="name" value="{{ $cfg['name'] ?? '' }}" required class="input-glass">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Base URL</label>
                                    <input name="base_url" value="{{ $cfg['base_url'] ?? '' }}" required type="url" class="input-glass font-mono text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Chat URL (opsional)</label>
                                    <input name="chat_url" value="{{ $cfg['chat_url'] ?? '' }}" type="url" class="input-glass font-mono text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Models URL (opsional)</label>
                                    <input name="models_url" value="{{ $cfg['models_url'] ?? '' }}" type="url" class="input-glass font-mono text-sm">
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'custom-edit-{{ $slug }}')">Batal</button>
                                    <button class="btn-primary">Simpan</button>
                                </div>
                            </form>
                        </x-modal-glass>

                        <x-modal-glass name="custom-del-{{ $slug }}" title="Hapus Provider Kustom" max-width="md">
                            <p class="text-slate-600 dark:text-slate-300">Hapus provider <span class="font-semibold">{{ $cfg['name'] ?? $slug }}</span>?</p>
                            <form method="POST" action="{{ url('/admin/ai/custom-providers/'.$slug) }}" class="flex justify-end gap-2 mt-5">
                                @csrf @method('DELETE')
                                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'custom-del-{{ $slug }}')">Batal</button>
                                <button class="btn-danger"><x-icon name="trash" class="w-4 h-4"/> Hapus</button>
                            </form>
                        </x-modal-glass>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ============= TAB: API KEY POOL ============= --}}
        <div x-show="tab==='keys'" x-cloak class="space-y-5">
            <div class="flex justify-end">
                <button class="btn-primary" type="button" @click="$dispatch('open-modal', 'key-create')">
                    <x-icon name="plus" class="w-4 h-4"/> Tambah Key
                </button>
            </div>

            @php $grouped = $keys->groupBy('provider'); @endphp

            @if($keys->isEmpty())
                <div class="glass p-10 text-center text-slate-500">
                    Belum ada API key. <a href="#" @click.prevent="$dispatch('open-modal', 'key-create')" class="text-emerald-600 hover:underline">Tambah key pertama →</a>
                </div>
            @else
                @foreach($grouped as $providerKey => $list)
                    <div class="glass p-4 sm:p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-slate-800 dark:text-slate-100">{{ $providers[$providerKey] ?? $providerKey }}</h3>
                                <p class="text-xs text-slate-500">{{ $list->count() }} key terdaftar · diurut berdasarkan prioritas</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ url('/admin/ai-keys/reorder') }}" id="reorder-{{ $providerKey }}">@csrf</form>

                        <div class="space-y-2" x-data="reorderable({ formId: 'reorder-{{ $providerKey }}' })">
                            @foreach($list as $k)
                                <div class="rounded-xl bg-white/50 dark:bg-slate-800/40 border border-white/60 dark:border-white/10 p-3"
                                     draggable="true"
                                     @dragstart="onStart($event, {{ $k->id }})"
                                     @dragover.prevent
                                     @drop="onDrop($event, {{ $k->id }})">
                                    <input type="hidden" name="order[]" value="{{ $k->id }}" form="reorder-{{ $providerKey }}">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono">#{{ $k->priority }}</span>
                                            <span class="badge {{ $k->is_active ? 'badge-emerald' : 'badge-slate' }}">{{ $k->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $k->label }}</p>
                                            <p class="text-xs text-slate-500 font-mono truncate">{{ $k->maskedKey() }} {{ $k->model ? '· '.$k->model : '' }}</p>
                                        </div>

                                        <div class="text-right">
                                            @php $rem = $k->quotaRemaining(); @endphp
                                            @if($k->quota_limit)
                                                <p class="text-xs text-slate-500">Sisa: <span class="font-bold text-slate-800 dark:text-slate-100">{{ number_format($rem) }}</span> / {{ number_format($k->quota_limit) }} <span class="opacity-70">({{ $k->quota_reset_period }})</span></p>
                                                <div class="w-32 h-1.5 bg-slate-200/60 dark:bg-slate-700 rounded-full mt-1 overflow-hidden">
                                                    <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500" style="width: {{ 100 - $k->quotaPercentUsed() }}%"></div>
                                                </div>
                                            @else
                                                <span class="badge badge-sky">Tak terbatas</span>
                                            @endif
                                            @if($k->last_used_at)
                                                <p class="text-[10px] text-slate-400 mt-1">terakhir dipakai {{ $k->last_used_at->diffForHumans() }}</p>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-1">
                                            <form method="POST" action="{{ url('/admin/ai-keys/'.$k->id.'/test') }}">@csrf
                                                <button class="btn-ghost p-2" title="Tes"><x-icon name="rocket" class="w-4 h-4"/></button>
                                            </form>
                                            <form method="POST" action="{{ url('/admin/ai-keys/'.$k->id.'/reset-quota') }}">@csrf
                                                <button class="btn-ghost p-2" title="Reset kuota"><x-icon name="history" class="w-4 h-4"/></button>
                                            </form>
                                            <button class="btn-ghost p-2" title="Edit" type="button" @click="$dispatch('open-modal', 'key-edit-{{ $k->id }}')"><x-icon name="pencil" class="w-4 h-4"/></button>
                                            <button class="btn-ghost p-2 text-rose-600" type="button" @click="$dispatch('open-modal', 'key-del-{{ $k->id }}')"><x-icon name="trash" class="w-4 h-4"/></button>
                                        </div>
                                    </div>

                                    @if($k->last_error)
                                        <p class="mt-2 text-xs text-rose-600 dark:text-rose-300 truncate">⚠ {{ $k->last_error }}</p>
                                    @endif
                                </div>

                                <x-modal-glass name="key-edit-{{ $k->id }}" title="Edit API Key" max-width="lg">
                                    <form method="POST" action="{{ url('/admin/ai-keys/'.$k->id) }}" class="space-y-3">
                                        @csrf @method('PUT')
                                        @include('admin.partials.ai-key-fields', ['providers' => $providers, 'k' => $k, 'aiService' => $aiService])
                                        <div class="flex justify-end gap-2 pt-2">
                                            <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'key-edit-{{ $k->id }}')">Batal</button>
                                            <button class="btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </x-modal-glass>

                                <x-modal-glass name="key-del-{{ $k->id }}" title="Hapus API Key" max-width="md">
                                    <p class="text-slate-600 dark:text-slate-300">Hapus key <span class="font-semibold">{{ $k->label }}</span>?</p>
                                    <form method="POST" action="{{ url('/admin/ai-keys/'.$k->id) }}" class="flex justify-end gap-2 mt-5">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'key-del-{{ $k->id }}')">Batal</button>
                                        <button class="btn-danger"><x-icon name="trash" class="w-4 h-4"/> Hapus</button>
                                    </form>
                                </x-modal-glass>
                            @endforeach
                        </div>

                        <div class="mt-3 flex justify-end">
                            <button form="reorder-{{ $providerKey }}" class="btn-secondary text-sm">
                                <x-icon name="arrow-up" class="w-4 h-4"/> Simpan Urutan
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Modal create --}}
    <x-modal-glass name="key-create" title="Tambah API Key" max-width="lg">
        <form method="POST" action="{{ url('/admin/ai-keys') }}" class="space-y-3">
            @csrf
            @include('admin.partials.ai-key-fields', ['providers' => $providers, 'k' => null, 'aiService' => $aiService])
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'key-create')">Batal</button>
                <button class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal-glass>

    <script>
        function reorderable({ formId }) {
            return {
                dragId: null,
                onStart(e, id) { this.dragId = id; e.dataTransfer.effectAllowed = 'move'; },
                onDrop(e, targetId) {
                    if (!this.dragId || this.dragId === targetId) return;
                    const form = document.getElementById(formId);
                    const inputs = Array.from(form.querySelectorAll('input[name="order[]"]'));
                    const fromIdx = inputs.findIndex(i => +i.value === this.dragId);
                    const toIdx   = inputs.findIndex(i => +i.value === targetId);
                    inputs[fromIdx].parentNode.insertBefore(inputs[fromIdx], inputs[toIdx]);
                    this.dragId = null;
                },
            }
        }

        function customProviderForm() {
            return {
                name: '',
                slug: '',
                baseUrl: '',
                chatUrl: '',
                modelsUrl: '',
                apiKey: '',
                previewLoading: false,
                previewError: '',
                previewCount: 0,
                previewModelsList: [],
                syncSlug() {
                    if (this.slug) return;
                    const s = this.name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
                    this.slug = s ? 'custom_' + s : '';
                },
                async previewModels() {
                    this.previewLoading = true;
                    this.previewError = '';
                    this.previewCount = 0;
                    this.previewModelsList = [];
                    try {
                        const params = new URLSearchParams({
                            base_url: this.baseUrl,
                            models_url: this.modelsUrl,
                            api_key: this.apiKey,
                        });
                        const res = await fetch('{{ url('/admin/ai/custom-providers/preview-models') }}?' + params.toString(), {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.message || 'Gagal fetch model.');
                        this.previewModelsList = data.models || [];
                        this.previewCount = data.count || 0;
                    } catch (e) {
                        this.previewError = e.message;
                    } finally {
                        this.previewLoading = false;
                    }
                },
            }
        }

        function aiGeneral({ provider, model }) {
            return {
                provider, model,
                models: [],
                loading: false,
                error: '',
                initial() { this.loadModels(false); },
                async loadModels(forceRefresh = false) {
                    this.loading = true;
                    this.error = '';
                    try {
                        const url = '{{ url('/admin/ai/models') }}?provider=' + encodeURIComponent(this.provider) + (forceRefresh ? '&refresh=1' : '');
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.message || 'Gagal memuat model.');
                        this.models = data.models || [];
                        // Jika model saat ini bukan dari daftar, tetap dipertahankan (boleh kustom).
                    } catch (e) {
                        this.models = [];
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                },
            }
        }
    </script>

</x-app-layout>

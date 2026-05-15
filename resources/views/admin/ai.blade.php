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
            <button type="button" @click="tab='keys'"
                    :class="tab==='keys' ? 'bg-emerald-500 text-white shadow' : 'text-slate-600 dark:text-slate-300'"
                    class="px-4 py-2 rounded-xl font-medium transition">
                <x-icon name="key" class="w-4 h-4 inline -mt-0.5"/> API Key Pool
                <span class="ml-1 text-xs opacity-80">({{ $keys->count() }})</span>
            </button>
        </div>

        {{-- ============= TAB: UMUM ============= --}}
        <div x-show="tab==='general'" x-cloak class="grid lg:grid-cols-3 gap-4 sm:gap-6"
             x-data="{ provider: '{{ $provider }}', model: '{{ $model }}' }">
            <div class="lg:col-span-2 glass p-4 sm:p-6">
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
                               required autocomplete="off"
                               placeholder="contoh: gemini-3.0-pro"
                               class="input-glass font-mono text-sm">
                        <p class="mt-1 text-xs text-slate-500">
                            Bebas ketik nama model preview/experimental. Setiap API Key juga boleh menimpa model ini di tab API Key Pool.
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
            </div>

            <div class="glass p-4 sm:p-6">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3 flex items-center gap-2">
                    <x-icon name="shield" class="w-5 h-5 text-emerald-600"/> Catatan
                </h3>
                <ul class="text-sm text-slate-600 dark:text-slate-300 space-y-2 list-disc pl-5">
                    <li>Banyak API key bisa digabung untuk satu provider; sistem otomatis berpindah jika satu kehabisan kuota.</li>
                    <li>Atur prioritas key di tab <span class="font-semibold">API Key Pool</span>.</li>
                    <li>Model Gemini didukung sampai seri <span class="font-mono">gemini-3.0-*</span>; Anda juga bisa mengetik model preview/experimental terbaru langsung.</li>
                    <li>Provider yang didukung: Gemini, OpenAI, Anthropic, OpenRouter, Groq.</li>
                </ul>
            </div>
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
    </script>
</x-app-layout>

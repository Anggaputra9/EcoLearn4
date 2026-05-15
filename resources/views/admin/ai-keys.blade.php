<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">API Key Pool</h2>
                <p class="text-sm text-slate-500">Kelola banyak API key per provider. Sistem akan menggunakan yang berprioritas paling tinggi (kecil duluan) dan otomatis berpindah jika kuota habis.</p>
            </div>
            <button class="btn-primary" @click="$dispatch('open-modal', 'key-create')">
                <x-icon name="plus" class="w-4 h-4"/> Tambah Key
            </button>
        </div>
    </x-slot>

    @php $grouped = $keys->groupBy('provider'); @endphp

    @if($keys->isEmpty())
        <div class="glass p-10 text-center text-slate-500">
            Belum ada API key. <a href="#" @click.prevent="$dispatch('open-modal', 'key-create')" class="text-emerald-600 hover:underline">Tambah key pertama →</a>
        </div>
    @else
        @foreach($grouped as $providerKey => $list)
            <div class="glass p-5 mb-6" x-data="{ }">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100">{{ $providers[$providerKey] ?? $providerKey }}</h3>
                        <p class="text-xs text-slate-500">{{ $list->count() }} key terdaftar · diurut berdasarkan prioritas</p>
                    </div>
                </div>

                <form method="POST" action="{{ url('/admin/ai-keys/reorder') }}" id="reorder-{{ $providerKey }}">
                    @csrf
                </form>

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
                                    @if($k->is_active)
                                        <span class="badge badge-emerald">Aktif</span>
                                    @else
                                        <span class="badge badge-slate">Nonaktif</span>
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $k->label }}</p>
                                    <p class="text-xs text-slate-500 font-mono">{{ $k->maskedKey() }} {{ $k->model ? '· '.$k->model : '' }}</p>
                                </div>

                                <div class="text-right">
                                    @php $rem = $k->quotaRemaining(); @endphp
                                    @if($k->quota_limit)
                                        <p class="text-xs text-slate-500">Sisa: <span class="font-bold text-slate-800 dark:text-slate-100">{{ $rem }}</span> / {{ $k->quota_limit }}</p>
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
                                    <button class="btn-ghost p-2" title="Edit" @click="$dispatch('open-modal', 'key-edit-{{ $k->id }}')"><x-icon name="pencil" class="w-4 h-4"/></button>
                                    <button class="btn-ghost p-2 text-rose-600" @click="$dispatch('open-modal', 'key-del-{{ $k->id }}')"><x-icon name="trash" class="w-4 h-4"/></button>
                                </div>
                            </div>

                            @if($k->last_error)
                                <p class="mt-2 text-xs text-rose-600 dark:text-rose-300 truncate">⚠ {{ $k->last_error }}</p>
                            @endif
                        </div>

                        {{-- Modal edit --}}
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
                    // also reorder visual nodes
                    const nodes = Array.from(e.currentTarget.parentElement.children).filter(n => n.tagName === 'DIV');
                    const fromIdx = inputs.findIndex(i => +i.value === this.dragId);
                    const toIdx   = inputs.findIndex(i => +i.value === targetId);
                    inputs[fromIdx].parentNode.insertBefore(inputs[fromIdx], inputs[toIdx]);
                    if (nodes[fromIdx] && nodes[toIdx]) nodes[toIdx].parentNode.insertBefore(nodes[fromIdx], nodes[toIdx]);
                    this.dragId = null;
                },
            }
        }
    </script>
</x-app-layout>

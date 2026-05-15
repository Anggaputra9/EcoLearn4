<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-lg sm:text-xl font-bold text-slate-800 dark:text-slate-100">Email & Notifikasi</h2>
                <p class="text-xs sm:text-sm text-slate-500">Provider email default & Mail Key Pool dalam satu halaman.</p>
            </div>
        </div>
    </x-slot>

    <div x-data="{ tab: '{{ request('tab', 'general') }}' }" class="space-y-5">
        {{-- Tabs --}}
        <div class="glass p-1.5 inline-flex flex-wrap gap-1 text-sm">
            <button type="button" @click="tab='general'"
                    :class="tab==='general' ? 'bg-emerald-500 text-white shadow' : 'text-slate-600 dark:text-slate-300'"
                    class="px-4 py-2 rounded-xl font-medium transition">
                <x-icon name="bell" class="w-4 h-4 inline -mt-0.5"/> Umum
            </button>
            <button type="button" @click="tab='keys'"
                    :class="tab==='keys' ? 'bg-emerald-500 text-white shadow' : 'text-slate-600 dark:text-slate-300'"
                    class="px-4 py-2 rounded-xl font-medium transition">
                <x-icon name="key" class="w-4 h-4 inline -mt-0.5"/> Mail Key Pool
                <span class="ml-1 text-xs opacity-80">({{ $keys->count() }})</span>
            </button>
        </div>

        {{-- ============= TAB: UMUM ============= --}}
        <div x-show="tab==='general'" x-cloak class="grid lg:grid-cols-3 gap-4 sm:gap-6"
             x-data="{ provider: '{{ $current }}' }">
            <div class="lg:col-span-2 glass p-4 sm:p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 grid place-items-center text-white shrink-0">
                        <x-icon name="bell" class="w-5 h-5"/>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Konfigurasi Pengirim</h3>
                        <p class="text-xs text-slate-500">Pilih satu provider; kredensial provider lain tetap tersimpan.</p>
                    </div>
                </div>

                <form method="POST" action="{{ url('/admin/email/general') }}" class="space-y-4">
                    @csrf @method('PUT')

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Provider</label>
                            <select name="provider" x-model="provider" class="input-glass">
                                @foreach($providers as $p => $name)
                                    <option value="{{ $p }}" @selected($current === $p)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Email Pengirim</label>
                            <input name="from_email" type="email" required value="{{ $fromEmail }}" class="input-glass">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Nama Pengirim</label>
                        <input name="from_name" required value="{{ $fromName }}" class="input-glass">
                    </div>

                    <div x-show="provider === 'brevo'" x-transition class="rounded-xl border border-white/40 dark:border-white/10 p-4 bg-white/40 dark:bg-slate-800/30">
                        <p class="font-semibold text-slate-800 dark:text-slate-100">Brevo (Sendinblue)</p>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mt-2 mb-1">API Key</label>
                        <input name="brevo_api_key" type="password" class="input-glass" placeholder="{{ $brevoSet ? '•••• tersimpan' : 'xkeysib-…' }}">
                        <p class="text-xs text-slate-500 mt-1">Dapatkan di <a class="text-emerald-600" target="_blank" href="https://app.brevo.com/settings/keys/api">app.brevo.com</a>.</p>
                    </div>

                    <div x-show="provider === 'mailersend'" x-transition class="rounded-xl border border-white/40 dark:border-white/10 p-4 bg-white/40 dark:bg-slate-800/30">
                        <p class="font-semibold text-slate-800 dark:text-slate-100">MailerSend</p>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mt-2 mb-1">API Token</label>
                        <input name="mailersend_api_key" type="password" class="input-glass" placeholder="{{ $mailerSet ? '•••• tersimpan' : 'mlsn.…' }}">
                    </div>

                    <div x-show="provider === 'sendpulse'" x-transition class="rounded-xl border border-white/40 dark:border-white/10 p-4 bg-white/40 dark:bg-slate-800/30">
                        <p class="font-semibold text-slate-800 dark:text-slate-100">SendPulse</p>
                        <div class="grid sm:grid-cols-2 gap-3 mt-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Client ID</label>
                                <input name="sendpulse_client_id" class="input-glass" placeholder="{{ $sendpulseSet ? '•••• tersimpan' : '' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Client Secret</label>
                                <input name="sendpulse_client_secret" type="password" class="input-glass">
                            </div>
                        </div>
                    </div>

                    <div x-show="provider === 'smtp'" x-transition class="rounded-xl border border-white/40 dark:border-white/10 p-4 bg-white/40 dark:bg-slate-800/30">
                        <p class="font-semibold text-slate-800 dark:text-slate-100">SMTP / PHPMailer</p>
                        <p class="text-xs text-slate-500 mt-1">Atur kredensial SMTP melalui variabel <code>.env</code> Laravel: <code>MAIL_MAILER</code>, <code>MAIL_HOST</code>, <code>MAIL_PORT</code>, <code>MAIL_USERNAME</code>, <code>MAIL_PASSWORD</code>, <code>MAIL_ENCRYPTION</code>.</p>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Simpan</button>
                    </div>
                </form>

                <hr class="my-6 border-white/40 dark:border-white/10">

                <form method="POST" action="{{ url('/admin/email/test') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[12rem]">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Tes kirim email ke</label>
                        <input name="to" type="email" required class="input-glass" value="{{ auth()->user()->email }}">
                    </div>
                    <button class="btn-secondary"><x-icon name="send" class="w-4 h-4"/> Kirim Tes</button>
                </form>
            </div>

            <div class="glass p-4 sm:p-6">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3 flex items-center gap-2">
                    <x-icon name="shield" class="w-5 h-5 text-emerald-600"/> Catatan
                </h3>
                <ul class="text-sm text-slate-600 dark:text-slate-300 space-y-2 list-disc pl-5">
                    <li>Notifikasi diskusi materi dikirim ke guru dan siswa secara otomatis.</li>
                    <li>Hasil ujian (jika diaktifkan oleh guru) juga dikirim sebagai email.</li>
                    <li>Kredensial disimpan terenkripsi di tabel <code>settings</code>.</li>
                    <li>Banyak API key bisa digabung untuk satu provider; sistem otomatis berpindah saat satu kehabisan kuota.</li>
                </ul>
            </div>
        </div>

        {{-- ============= TAB: MAIL KEY POOL ============= --}}
        <div x-show="tab==='keys'" x-cloak class="space-y-5">
            <div class="flex justify-end">
                <button type="button" class="btn-primary" @click="$dispatch('open-modal', 'mk-create')">
                    <x-icon name="plus" class="w-4 h-4"/> Tambah Key
                </button>
            </div>

            @php $grouped = $keys->groupBy('provider'); @endphp

            @if($keys->isEmpty())
                <div class="glass p-10 text-center text-slate-500 dark:text-slate-400">
                    Belum ada mail key. <a href="#" @click.prevent="$dispatch('open-modal', 'mk-create')" class="text-emerald-600 hover:underline">Tambah key pertama →</a>
                </div>
            @else
                @foreach($grouped as $providerKey => $list)
                    <div class="glass p-4 sm:p-5">
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3">{{ $keyProviders[$providerKey] ?? $providerKey }}</h3>
                        <div class="space-y-2">
                            @foreach($list as $k)
                                <div class="rounded-xl bg-white/50 dark:bg-slate-800/40 border border-white/60 dark:border-white/10 p-3 flex flex-wrap items-center gap-3">
                                    <span class="text-xs px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-200 font-mono">#{{ $k->priority }}</span>
                                    <span class="badge {{ $k->is_active ? 'badge-emerald' : 'badge-slate' }}">{{ $k->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $k->label }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 font-mono truncate">{{ $k->maskedKey() }}</p>
                                    </div>
                                    <div class="text-right">
                                        @if($k->quota_limit)
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Sisa <span class="font-bold text-slate-800 dark:text-slate-100">{{ number_format($k->quotaRemaining()) }}</span> / {{ number_format($k->quota_limit) }} <span class="opacity-70">({{ $k->quota_reset_period }})</span></p>
                                        @else
                                            <span class="badge badge-sky">Tak terbatas</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <form method="POST" action="{{ url('/admin/mail-keys/'.$k->id.'/reset-quota') }}">@csrf
                                            <button class="btn-ghost p-2" title="Reset kuota"><x-icon name="history" class="w-4 h-4"/></button>
                                        </form>
                                        <button type="button" class="btn-ghost p-2" @click="$dispatch('open-modal', 'mk-edit-{{ $k->id }}')"><x-icon name="pencil" class="w-4 h-4"/></button>
                                        <button type="button" class="btn-ghost p-2 text-rose-600" @click="$dispatch('open-modal', 'mk-del-{{ $k->id }}')"><x-icon name="trash" class="w-4 h-4"/></button>
                                    </div>
                                    @if($k->last_error)
                                        <p class="w-full mt-1 text-xs text-rose-600 dark:text-rose-300 truncate">⚠ {{ $k->last_error }}</p>
                                    @endif
                                </div>

                                <x-modal-glass name="mk-edit-{{ $k->id }}" title="Edit Mail Key" max-width="lg">
                                    <form method="POST" action="{{ url('/admin/mail-keys/'.$k->id) }}" class="space-y-3">
                                        @csrf @method('PUT')
                                        @include('admin.partials.mail-key-fields', ['providers' => $keyProviders, 'k' => $k])
                                        <div class="flex justify-end gap-2 pt-2">
                                            <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'mk-edit-{{ $k->id }}')">Batal</button>
                                            <button class="btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </x-modal-glass>

                                <x-modal-glass name="mk-del-{{ $k->id }}" title="Hapus Mail Key" max-width="md">
                                    <p class="text-slate-600 dark:text-slate-300">Hapus key <span class="font-semibold">{{ $k->label }}</span>?</p>
                                    <form method="POST" action="{{ url('/admin/mail-keys/'.$k->id) }}" class="flex justify-end gap-2 mt-5">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'mk-del-{{ $k->id }}')">Batal</button>
                                        <button class="btn-danger"><x-icon name="trash" class="w-4 h-4"/> Hapus</button>
                                    </form>
                                </x-modal-glass>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <x-modal-glass name="mk-create" title="Tambah Mail Key" max-width="lg">
        <form method="POST" action="{{ url('/admin/mail-keys') }}" class="space-y-3">
            @csrf
            @include('admin.partials.mail-key-fields', ['providers' => $keyProviders, 'k' => null])
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'mk-create')">Batal</button>
                <button class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal-glass>
</x-app-layout>

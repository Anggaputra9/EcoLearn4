<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Email & Notifikasi</h2>
        <p class="text-sm text-slate-500">Pilih provider pengirim email dan kelola kredensialnya.</p>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-6" x-data="{ provider: '{{ $current }}' }">
        <div class="lg:col-span-2 glass p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 grid place-items-center text-white">
                    <x-icon name="bell" class="w-5 h-5"/>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Konfigurasi Pengirim</h3>
                    <p class="text-xs text-slate-500">Pilih satu provider; kredensial provider lain tetap tersimpan.</p>
                </div>
            </div>

            <form method="POST" action="{{ url('/admin/mail') }}" class="space-y-4">
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

            <form method="POST" action="{{ url('/admin/mail/test') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="flex-1 min-w-0">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Tes kirim email ke</label>
                    <input name="to" type="email" required class="input-glass" value="{{ auth()->user()->email }}">
                </div>
                <button class="btn-secondary"><x-icon name="send" class="w-4 h-4"/> Kirim Tes</button>
            </form>
        </div>

        <div class="glass p-6">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-3 flex items-center gap-2">
                <x-icon name="shield" class="w-5 h-5 text-emerald-600"/> Catatan
            </h3>
            <ul class="text-sm text-slate-600 dark:text-slate-300 space-y-2 list-disc pl-5">
                <li>Notifikasi diskusi materi dikirim ke guru dan siswa secara otomatis.</li>
                <li>Hasil ujian (jika diaktifkan oleh guru) juga dikirim sebagai email.</li>
                <li>Kredensial disimpan terenkripsi di tabel <code>settings</code>.</li>
            </ul>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Pengaturan Aplikasi</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400">Atur nama, tagline, dan footer yang tampil di seluruh sistem.</p>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 glass p-6">
            <form method="POST" action="{{ url('/admin/app') }}" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Nama Aplikasi</label>
                    <input name="app_name" required maxlength="120" value="{{ $appName }}" class="input-glass">
                    <p class="text-xs text-slate-500 mt-1">Tampil di sidebar, tab browser, dan email notifikasi.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Tagline / Deskripsi</label>
                    <input name="app_tagline" value="{{ $appTagline }}" maxlength="255" class="input-glass">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Footer</label>
                    <input name="app_footer" value="{{ $appFooter }}" maxlength="255" class="input-glass">
                </div>
                <div class="flex justify-end pt-2">
                    <button class="btn-primary"><x-icon name="check" class="w-4 h-4"/> Simpan</button>
                </div>
            </form>
        </div>

        <div class="glass p-6">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-3">
                <x-icon name="shield" class="w-5 h-5 text-emerald-600"/> Catatan
            </h3>
            <ul class="text-sm text-slate-600 dark:text-slate-300 space-y-2 list-disc pl-5">
                <li>Nilai di sini akan menimpa <code>APP_NAME</code> dari <code>.env</code>.</li>
                <li>Refresh halaman setelah simpan untuk melihat efeknya di sidebar/topbar.</li>
            </ul>
        </div>
    </div>
</x-app-layout>

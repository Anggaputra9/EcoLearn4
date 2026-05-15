<x-guest-layout>
    <h2 class="text-2xl font-bold text-slate-800 mb-1">Verifikasi Email</h2>
    <p class="text-sm text-slate-500 mb-6">
        Kami sudah mengirim kode 6 digit ke
        <span class="font-semibold text-emerald-700">{{ $email }}</span>.
        Masukkan kode untuk menyelesaikan pendaftaran. Kode berlaku 10 menit.
    </p>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 px-4 py-3 mb-4 text-emerald-700 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50/60 px-4 py-3 mb-4 text-rose-700 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50/60 px-4 py-3 mb-4 text-rose-700 text-sm">
            @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register.otp.verify') }}" class="space-y-4"
          x-data="otpForm()" x-init="init()">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Kode OTP</label>
            <div class="flex justify-between gap-2">
                <template x-for="(d, i) in 6" :key="i">
                    <input type="text" inputmode="numeric" maxlength="1"
                           :data-idx="i"
                           x-on:input="onInput($event, i)"
                           x-on:keydown="onKeydown($event, i)"
                           x-on:paste="onPaste($event)"
                           class="w-12 h-14 sm:w-14 sm:h-16 input-glass text-center text-2xl font-bold tracking-widest font-mono"
                           required>
                </template>
            </div>
            <input type="hidden" name="code" :value="value" maxlength="6">
        </div>

        <button class="btn-primary w-full py-3" :disabled="value.length !== 6">Verifikasi & Buat Akun</button>

        <div class="flex items-center justify-between text-sm">
            <a href="{{ route('register') }}" class="text-slate-500 hover:text-slate-700">← Ganti email</a>
        </div>
    </form>

    <form method="POST" action="{{ route('register.otp.resend') }}" class="mt-3 text-center">
        @csrf
        <button class="text-sm text-emerald-600 hover:underline font-medium">Kirim ulang kode</button>
    </form>

    <script>
        function otpForm() {
            return {
                value: '',
                init() {
                    this.$nextTick(() => this.$root.querySelector('input[data-idx="0"]')?.focus());
                },
                cells() { return [...this.$root.querySelectorAll('input[data-idx]')]; },
                sync() {
                    this.value = this.cells().map(c => c.value).join('').slice(0, 6);
                },
                onInput(e, i) {
                    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 1);
                    this.sync();
                    if (e.target.value && i < 5) {
                        this.cells()[i + 1].focus();
                    }
                    if (this.value.length === 6) {
                        e.target.form.requestSubmit();
                    }
                },
                onKeydown(e, i) {
                    if (e.key === 'Backspace' && !e.target.value && i > 0) {
                        this.cells()[i - 1].focus();
                    }
                },
                onPaste(e) {
                    const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                    if (!pasted) return;
                    e.preventDefault();
                    const cs = this.cells();
                    for (let i = 0; i < 6; i++) cs[i].value = pasted[i] || '';
                    this.sync();
                    cs[Math.min(pasted.length, 5)].focus();
                    if (this.value.length === 6) e.target.form.requestSubmit();
                },
            }
        }
    </script>
</x-guest-layout>

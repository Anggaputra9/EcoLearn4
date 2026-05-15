<x-guest-layout>
    <h2 class="text-2xl font-bold text-slate-800 mb-1">Masuk</h2>
    <p class="text-sm text-slate-500 mb-6">Kelola pembelajaran ekoteologi Anda.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50/60 px-4 py-3 mb-4 text-rose-700 text-sm">
            @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input name="email" type="email" required autofocus value="{{ old('email') }}" class="input-glass">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi</label>
            <input name="password" type="password" required class="input-glass">
        </div>
        <div class="flex items-center justify-between text-sm">
            <label class="inline-flex items-center gap-2 text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span>Ingat saya</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-emerald-600 hover:underline">Lupa sandi?</a>
            @endif
        </div>

        <button class="btn-primary w-full py-3">Masuk</button>

        @if (Route::has('register'))
            <p class="text-center text-sm text-slate-500">
                Belum punya akun? <a href="{{ route('register') }}" class="text-emerald-600 font-medium hover:underline">Daftar</a>
            </p>
        @endif
    </form>
</x-guest-layout>

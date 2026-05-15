<x-guest-layout>
    <h2 class="text-2xl font-bold text-slate-800 mb-1">Daftar</h2>
    <p class="text-sm text-slate-500 mb-6">Buat akun untuk mulai belajar atau mengajar.</p>

    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50/60 px-4 py-3 mb-4 text-rose-700 text-sm">
            @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama</label>
            <input name="name" required autofocus value="{{ old('name') }}" class="input-glass">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input name="email" type="email" required value="{{ old('email') }}" class="input-glass">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi</label>
            <input name="password" type="password" required minlength="8" class="input-glass">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Sandi</label>
            <input name="password_confirmation" type="password" required minlength="8" class="input-glass">
        </div>

        <button class="btn-primary w-full py-3">Daftar Akun</button>

        <p class="text-center text-sm text-slate-500">
            Sudah punya akun? <a href="{{ route('login') }}" class="text-emerald-600 font-medium hover:underline">Masuk</a>
        </p>
    </form>
</x-guest-layout>

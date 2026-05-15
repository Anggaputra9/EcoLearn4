<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eko-Scribe • Pembelajaran Esai Ekoteologi Berbasis AI</title>
    <meta name="description" content="Platform pembelajaran otomatis bertema ekoteologi. Guru membuat materi & soal dengan AI, siswa mengerjakan esai, dan mendapatkan koreksi AI seketika.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-700">
<div class="landing-bg">
    {{-- NAV --}}
    <nav class="px-4 sm:px-6 lg:px-10 pt-6">
        <div class="glass max-w-6xl mx-auto flex items-center gap-3 px-5 py-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 grid place-items-center shadow-lg">
                    <x-icon name="leaf" class="w-5 h-5 text-white"/>
                </div>
                <div>
                    <p class="font-bold text-slate-800">Eko-Scribe</p>
                    <p class="text-[10px] uppercase tracking-wider text-emerald-600 font-semibold">v{{ config('app.version') }}</p>
                </div>
            </div>
            <div class="ml-auto flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary">Masuk Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary">Masuk</a>
                    <a href="{{ route('register') }}" class="btn-primary">Daftar</a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- HERO --}}
    <section class="px-4 sm:px-6 lg:px-10 pt-16 pb-20">
        <div class="max-w-6xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="badge badge-emerald mb-4">
                    <x-icon name="sparkles" class="w-3 h-3"/> Powered by Google Gemini
                </span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-tight">
                    Pembelajaran
                    <span class="bg-gradient-to-r from-emerald-600 via-teal-500 to-cyan-500 bg-clip-text text-transparent">
                        Ekoteologi
                    </span>
                    Berbasis AI.
                </h1>
                <p class="mt-5 text-lg text-slate-600 leading-relaxed">
                    Platform untuk guru menyusun materi & soal otomatis, dan untuk siswa mengerjakan esai
                    yang langsung dikoreksi AI dengan skor 0-100 plus umpan balik pedagogis.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-primary px-6 py-3">
                        <x-icon name="rocket" class="w-5 h-5"/> Mulai Gratis
                    </a>
                    <a href="#fitur" class="btn-secondary px-6 py-3">
                        Pelajari Fitur <x-icon name="arrow-right" class="w-4 h-4"/>
                    </a>
                </div>
                <div class="mt-8 flex items-center gap-4 text-sm text-slate-500">
                    <div class="flex -space-x-2">
                        <div class="w-8 h-8 rounded-full bg-emerald-200 ring-2 ring-white"></div>
                        <div class="w-8 h-8 rounded-full bg-teal-200 ring-2 ring-white"></div>
                        <div class="w-8 h-8 rounded-full bg-cyan-200 ring-2 ring-white"></div>
                    </div>
                    <span>3 peran terintegrasi: Admin · Guru · Siswa</span>
                </div>
            </div>

            <div class="relative">
                <div class="glass-strong p-6 rotate-1">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-3 h-3 rounded-full bg-rose-400"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                    </div>
                    <p class="text-xs text-slate-500">Soal Esai · Tingkat SMA</p>
                    <p class="font-semibold text-slate-800 mt-1">Bagaimana konsep stewardship dapat menjadi etika ekologis dalam kehidupan sehari-hari?</p>
                    <div class="mt-4 p-4 rounded-xl bg-emerald-50/70 border border-emerald-200/60">
                        <p class="text-xs uppercase font-semibold text-emerald-700">Skor AI</p>
                        <p class="text-5xl font-bold text-emerald-600 mt-1">87<span class="text-lg text-slate-400">/100</span></p>
                        <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                            Jawaban menunjukkan pemahaman kuat tentang stewardship sebagai tanggung jawab pengelolaan…
                        </p>
                    </div>
                </div>
                <div class="absolute -bottom-6 -left-6 glass p-4 -rotate-3 hidden sm:block">
                    <div class="flex items-center gap-2">
                        <x-icon name="check" class="w-5 h-5 text-emerald-600"/>
                        <span class="text-sm font-medium">Dikoreksi dalam 3 detik</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FITUR --}}
    <section id="fitur" class="px-4 sm:px-6 lg:px-10 py-16">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <span class="badge badge-violet">Fitur Utama</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-slate-800">Lengkap untuk siklus belajar esai</h2>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach([
                    ['sparkles','Generator Materi AI','Guru cukup memberi topik, AI menyusun materi lengkap berbasis ekoteologi.'],
                    ['doc-text','Soal Otomatis','Hasilkan beragam soal esai reflektif dari materi yang sudah ada.'],
                    ['chart','Koreksi AI Pedagogis','Skor 0-100 + umpan balik konstruktif dalam Bahasa Indonesia.'],
                    ['users','Multi Peran','Admin, Guru, dan Siswa dengan dashboard masing-masing.'],
                    ['cog','Konfigurasi Dinamis','Admin dapat mengubah API key & model AI tanpa edit kode.'],
                    ['shield','Aman & Terstruktur','Hashing bcrypt, role-based middleware, audit changelog.'],
                ] as [$icon, $title, $desc])
                    <div class="glass p-6 hover:scale-[1.02] transition">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 grid place-items-center text-white shadow-lg">
                            <x-icon :name="$icon" class="w-5 h-5"/>
                        </div>
                        <h3 class="mt-4 font-semibold text-slate-800">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="px-4 sm:px-6 lg:px-10 py-16">
        <div class="max-w-4xl mx-auto glass-strong p-10 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-800">Siap mengajar lebih cerdas?</h2>
            <p class="mt-3 text-slate-600">Bergabung sekarang dan biarkan AI membantu Anda menyusun, mengoreksi, dan menumbuhkan generasi pencinta lingkungan.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="btn-primary px-6 py-3"><x-icon name="rocket" class="w-5 h-5"/> Daftar Akun</a>
                <a href="{{ route('login') }}" class="btn-secondary px-6 py-3">Masuk</a>
            </div>
        </div>
    </section>

    <footer class="px-4 sm:px-6 lg:px-10 py-8 text-center text-xs text-slate-500">
        © {{ now()->year }} Eko-Scribe v{{ config('app.version') }} • Dibuat dengan ♥ untuk pendidikan ekologis
    </footer>
</div>
</body>
</html>

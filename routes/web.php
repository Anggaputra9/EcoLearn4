<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Services\GeminiAIService;

// 1. Paksa halaman utama (root) langsung mengarah ke halaman Login
Route::get('/', function () {
    return redirect('/login');
});

// 2. Rute Dashboard setelah berhasil login (Bawaan Breeze)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// 3. Rute Generate AI (Nanti kita rapikan ke menu khusus Guru, sementara dilindungi auth)
Route::post('/generate', function (Request $request, GeminiAIService $ai) {
    $request->validate(['topik' => 'required']);
    $topik = $request->topik;
    $prompt = "Sebagai asisten guru, buatkan 3 soal pilihan ganda tingkat sekolah dasar tentang '$topik'. Berikan juga kunci jawabannya di bagian akhir.";
    $hasil = $ai->generateText($prompt);
    return view('welcome', ['hasil' => $hasil, 'topik' => $topik]);
})->middleware(['auth']);

// Memanggil rute otentikasi bawaan Laravel Breeze
require __DIR__.'/auth.php';

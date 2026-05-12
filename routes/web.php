<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\GeminiAIService;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;

Route::get('/', function () { return redirect('/login'); });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // === CRUD ADMIN ===
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::get('/admin/users/create', [AdminController::class, 'createUser']);
    Route::post('/admin/users', [AdminController::class, 'storeUser']);
    Route::get('/admin/users/{id}/edit', [AdminController::class, 'editUser']);
    Route::put('/admin/users/{id}', [AdminController::class, 'updateUser']);
    Route::delete('/admin/users/{id}', [AdminController::class, 'destroyUser']);
    
    Route::get('/admin/menus', [AdminController::class, 'menus']);
    Route::get('/admin/menus/create', [AdminController::class, 'createMenu']);
    Route::post('/admin/menus', [AdminController::class, 'storeMenu']);
    Route::get('/admin/menus/{id}/edit', [AdminController::class, 'editMenu']);
    Route::put('/admin/menus/{id}', [AdminController::class, 'updateMenu']);
    Route::delete('/admin/menus/{id}', [AdminController::class, 'destroyMenu']);

    // === FITUR AI GURU ===
    Route::get('/guru/soal', function () { return view('guru.soal', ['hasil' => null, 'topik' => '']); });
    Route::post('/guru/generate', function (Request $request, GeminiAIService $ai) {
        $request->validate(['topik' => 'required']);
        $prompt = "Sebagai asisten guru, buatkan 3 soal pilihan ganda tingkat sekolah dasar tentang '{$request->topik}'. Berikan juga kunci jawabannya di bagian akhir.";
        return view('guru.soal', ['hasil' => $ai->generateText($prompt), 'topik' => $request->topik]);
    });
});

// === PANGGIL RUTE LOGIN RESMI DI SINI ===
// (Diletakkan sebelum rute ajaib agar terbaca lebih dulu)
require __DIR__.'/auth.php';

// === RUTE AJAIB (Catch-All) HARUS DI POSISI PALING BAWAH ===
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/{slug}', function (Request $request) {
        $url = '/' . ltrim($request->path(), '/');
        $menu = DB::table('menus')->where('url', $url)->where('role_id', auth()->user()->role_id)->first();
        if ($menu) {
            return view('dynamic-page', ['menu' => $menu]);
        }
        abort(404);
    })->where('slug', '.*');
});

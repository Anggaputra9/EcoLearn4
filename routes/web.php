<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () { return redirect('/login'); });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard');

    // FITUR MATERI AI
    Route::get('/guru/generate-materi', [MateriController::class, 'createAi']);
    Route::post('/guru/generate-materi', [MateriController::class, 'generate']);
    Route::post('/guru/save-pdf', [MateriController::class, 'saveAsPdf']);

    // DAFTAR MATERI
    Route::get('/guru/materi', [MateriController::class, 'indexGuru']);
    Route::get('/siswa/materi', [MateriController::class, 'indexSiswa']);
    Route::get('/materi', [MateriController::class, 'indexSiswa']);

    // ADMIN & PROFILE
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::get('/admin/menus', [AdminController::class, 'menus']);
});

require __DIR__.'/auth.php';

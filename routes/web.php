<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\GeminiAIService;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MateriController;

Route::get('/', function () { return redirect('/login'); });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard');

    // === RUTE PROFIL (INI YANG HILANG SEBELUMNYA) ===
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

    // === FITUR MATERI AI ===
    Route::get('/guru/generate-materi', [MateriController::class, 'createAi']);
    Route::post('/guru/generate-materi', [MateriController::class, 'generate']);
    Route::post('/guru/save-pdf', [MateriController::class, 'saveAsPdf']);

    // === DAFTAR MATERI ===
    Route::get('/guru/materi', [MateriController::class, 'indexGuru']);
    Route::get('/siswa/materi', [MateriController::class, 'indexSiswa']);
    Route::get('/materi', [MateriController::class, 'indexSiswa']);
});

require __DIR__.'/auth.php';

// === RUTE AJAIB (Catch-All) DIKEMBALIKAN ===
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

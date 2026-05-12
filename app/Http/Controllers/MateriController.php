<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MateriController extends Controller
{
    // === TAMPILAN GURU (UPLOAD) ===
    public function indexGuru() {
        $materis = DB::table('materis')->where('guru_id', auth()->id())->get();
        return view('guru.materi', compact('materis'));
    }

    public function store(Request $request) {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'file_materi' => 'required|file|mimes:pdf,doc,docx,ppt,pptx|max:5120' // Maks 5MB
        ]);

        // Simpan file ke folder storage/app/public/materis
        $path = $request->file('file_materi')->store('materis', 'public');

        DB::table('materis')->insert([
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'file_path' => $path,
            'guru_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Materi berhasil diunggah!');
    }

    // === TAMPILAN SISWA (MENGAKSES) ===
    public function indexSiswa() {
        // Ambil semua materi beserta nama guru yang mengunggahnya
        $materis = DB::table('materis')
            ->join('users', 'materis.guru_id', '=', 'users.id')
            ->select('materis.*', 'users.name as nama_guru')
            ->orderBy('materis.id', 'desc')
            ->get();
        return view('siswa.materi', compact('materis'));
    }
}

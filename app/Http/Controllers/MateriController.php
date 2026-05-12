<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\GeminiAIService;
use Barryvdh\DomPDF\Facade\Pdf;

class MateriController extends Controller
{
    // Halaman Form AI Guru
    public function createAi() {
        return view('guru.generate-materi', ['hasil' => null, 'topik' => '']);
    }

    // Proses Generate AI & Pembersihan Bintang
    public function generate(Request $request, GeminiAIService $ai) {
        $request->validate(['topik' => 'required']);
        
        $prompt = "Buatkan materi pembelajaran yang lengkap dan mendalam untuk tingkat sekolah tentang: '{$request->topik}'. Buat dalam format sub-judul yang rapi tanpa menggunakan simbol markdown seperti bintang-bintang.";
        
        $rawText = $ai->generateText($prompt);
        
        // Membersihkan simbol ** (bintang) dari hasil AI
        $cleanText = str_replace(['**', '*'], '', $rawText);

        return view('guru.generate-materi', [
            'hasil' => $cleanText,
            'topik' => $request->topik
        ]);
    }

    // Fungsi Simpan ke PDF dan Kirim ke Siswa
    public function saveAsPdf(Request $request) {
        $request->validate(['judul' => 'required', 'konten' => 'required']);

        // 1. Generate PDF dari teks hasil AI
        $pdf = Pdf::loadHTML("
            <h1 style='text-align:center; color:#059669;'>{$request->judul}</h1>
            <div style='white-space: pre-wrap; font-family: sans-serif; line-height: 1.6;'>
                {$request->konten}
            </div>
        ");

        // 2. Simpan file PDF ke storage
        $fileName = 'materi_' . time() . '.pdf';
        $path = 'materis/' . $fileName;
        Storage::disk('public')->put($path, $pdf->output());

        // 3. Masukkan ke database agar siswa bisa melihat
        DB::table('materis')->insert([
            'judul' => $request->judul,
            'deskripsi' => 'Materi hasil generate AI tentang ' . $request->judul,
            'file_path' => $path,
            'guru_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/guru/materi')->with('success', 'Materi AI berhasil diubah ke PDF dan dikirim ke Siswa!');
    }

    // Tampilan Siswa
    public function indexSiswa() {
        $materis = DB::table('materis')
            ->join('users', 'materis.guru_id', '=', 'users.id')
            ->select('materis.*', 'users.name as nama_guru')
            ->orderBy('materis.id', 'desc')
            ->get();
        return view('siswa.materi', compact('materis'));
    }

    // Daftar Materi Guru
    public function indexGuru() {
        $materis = DB::table('materis')->where('guru_id', auth()->id())->get();
        return view('guru.materi', compact('materis'));
    }
}

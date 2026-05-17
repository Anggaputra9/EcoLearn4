<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Material;
use App\Models\Question;
use App\Services\AIService;
use App\Services\MaterialExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $level = $request->get('level');
        $classroomId = $request->get('classroom_id');

        $materials = Material::with(['questions', 'classroom'])
            ->where('teacher_id', Auth::id())
            ->when($q, fn ($qq) => $qq->where(function ($w) use ($q) {
                $w->where('title', 'like', "%$q%")->orWhere('topic', 'like', "%$q%");
            }))
            ->when($level, fn ($qq) => $qq->where('level', $level))
            ->when($classroomId, fn ($qq) => $qq->where('classroom_id', $classroomId))
            ->orderByRaw('meeting_number IS NULL, meeting_number ASC')
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $classrooms = Classroom::where('teacher_id', Auth::id())->orderBy('name')->get();

        // Untuk auto-iterate nomor pertemuan di modal "Buat Materi".
        // Default: skala "tanpa kelas" milik guru ini.
        $nextMeeting = Material::nextMeetingNumber(Auth::id(), null);

        // Hitung sisa materi yang ada di histori (sudah dihapus tapi masih bisa di-restore)
        $trashedCount = Material::onlyTrashed()->where('teacher_id', Auth::id())->count();

        return view('teacher.index', compact(
            'materials', 'q', 'level', 'classrooms', 'classroomId', 'nextMeeting', 'trashedCount'
        ));
    }

    /**
     * AJAX: kembalikan nomor pertemuan berikutnya berdasarkan classroom_id.
     * Dipakai modal "Buat Materi" untuk auto-iterate saat guru ganti kelas.
     */
    public function nextMeetingNumber(Request $request): JsonResponse
    {
        $classroomId = $request->input('classroom_id') ?: null;
        if ($classroomId) {
            $own = Classroom::where('id', $classroomId)->where('teacher_id', Auth::id())->exists();
            if (! $own) abort(403);
        }
        return response()->json([
            'next' => Material::nextMeetingNumber(Auth::id(), $classroomId ? (int) $classroomId : null),
        ]);
    }

    /**
     * Halaman "Histori Materi" — termasuk yang sudah dihapus.
     * Mirip log history: guru bisa lihat bekas materi lama, restore, atau hapus permanen.
     */
    public function materialHistory(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $classroomId = $request->get('classroom_id');
        $scope = $request->get('scope', 'all'); // all | active | trashed

        $base = Material::withTrashed()
            ->with(['questions', 'classroom'])
            ->where('teacher_id', Auth::id());

        if ($scope === 'active') $base->whereNull('deleted_at');
        if ($scope === 'trashed') $base->whereNotNull('deleted_at');

        $materials = $base
            ->when($q, fn ($qq) => $qq->where(function ($w) use ($q) {
                $w->where('title', 'like', "%$q%")->orWhere('topic', 'like', "%$q%");
            }))
            ->when($classroomId, fn ($qq) => $qq->where('classroom_id', $classroomId))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $classrooms = Classroom::where('teacher_id', Auth::id())->orderBy('name')->get();

        return view('teacher.material-history', compact('materials', 'q', 'classrooms', 'classroomId', 'scope'));
    }

    public function restoreMaterial(int $id): RedirectResponse
    {
        $material = Material::onlyTrashed()->where('teacher_id', Auth::id())->findOrFail($id);
        $material->restore();
        return back()->with('success', 'Materi "'.$material->title.'" berhasil dipulihkan.');
    }

    public function forceDestroyMaterial(int $id): RedirectResponse
    {
        $material = Material::withTrashed()->where('teacher_id', Auth::id())->findOrFail($id);
        $title = $material->title;
        $material->forceDelete();
        return back()->with('success', 'Materi "'.$title.'" dihapus permanen.');
    }


    /**
     * Endpoint AJAX modal "Buat Materi".
     *
     * Mendukung multi-format (mirip NotebookLM): guru bisa memilih
     * beberapa format sekaligus (materi lengkap, ringkasan, slide,
     * infografis, mind map, dst). Selain itu guru bisa menambahkan
     * `custom_prompt` agar arah penyusunan materi lebih spesifik
     * daripada sekadar "topik".
     *
     * Response:
     *   { ok: true, outputs: [{format, label, content}, ...] }
     */
    public function generateMaterialAjax(Request $request, AIService $ai): JsonResponse
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'topic'         => 'required|string|max:255',
            'level'         => 'required|in:SD,SMP,SMA,Umum',
            'formats'       => 'required|array|min:1',
            'formats.*'     => 'required|string|in:'.implode(',', array_keys(Material::formats())),
            'custom_prompt' => 'nullable|string|max:2000',
        ]);

        $formats = array_values(array_unique($data['formats']));
        $custom = trim((string) ($data['custom_prompt'] ?? ''));

        $outputs = [];
        $errors  = [];

        foreach ($formats as $fmt) {
            try {
                $text = $this->generateForFormat($ai, $fmt, $data['title'], $data['topic'], $data['level'], $custom);
                $outputs[] = [
                    'format'  => $fmt,
                    'label'   => Material::formatLabel($fmt),
                    'content' => $text,
                ];
            } catch (\Throwable $e) {
                $errors[] = Material::formatLabel($fmt).': '.$e->getMessage();
            }
        }

        if (empty($outputs)) {
            return response()->json([
                'ok' => false,
                'message' => $errors ? implode(' | ', $errors) : 'AI tidak mengembalikan hasil.',
            ], 422);
        }

        return response()->json([
            'ok'      => true,
            'outputs' => $outputs,
            'partial' => $errors,
        ]);
    }

    /**
     * Bangun prompt sesuai format & panggil AI. Dipisah agar bisa dipakai
     * ulang (mis. saat regenerate satu format dari halaman edit).
     */
    protected function generateForFormat(
        AIService $ai,
        string $format,
        string $title,
        string $topic,
        string $level,
        string $customPrompt = ''
    ): string {
        $system = 'Anda adalah penyusun materi pembelajaran berbahasa Indonesia dengan spesialisasi '
                .'ekoteologi (perpaduan teologi & ekologi). Output harus mendidik, akurat, ramah '
                .'pelajar, dan inklusif. Jangan pakai simbol markdown (**, *, #) atau emoji.';

        $base = "Topik utama: \"{$topic}\".\nJudul materi: \"{$title}\".\nTingkat sasaran: {$level}.";
        if ($customPrompt !== '') {
            $base .= "\nArahan tambahan dari guru (WAJIB diikuti): {$customPrompt}";
        }

        $prompt = match ($format) {
            'summary' => $base."\n\nTugas: tulis RINGKASAN materi pembelajaran. "
                ."Maksimal 250 kata, padat & mudah dipahami. Susun dalam paragraf pengantar 1-2 kalimat, "
                ."lalu daftar 5-7 poin inti dengan format 'Nomor. Judul Poin: penjelasan 1 kalimat'. "
                ."Tutup dengan 1 kalimat refleksi nilai ekoteologi.",

            'slides' => $base."\n\nTugas: susun OUTLINE SLIDE PRESENTASI siap pakai (8-12 slide). "
                ."Format setiap slide:\n"
                ."Slide N: <Judul Singkat>\n- Poin bullet 1\n- Poin bullet 2\n- Poin bullet 3\n"
                ."Catatan Pengajar: <1-2 kalimat untuk dibawakan lisan>\n\n"
                ."Slide pertama = sampul, slide terakhir = penutup/refleksi. Jangan gunakan tanda **/##.",

            'infographic' => $base."\n\nTugas: buat NASKAH INFOGRAFIS berbasis teks (tanpa gambar) "
                ."yang mudah dialihkan ke desain visual. Struktur:\n"
                ."Judul Utama:\nSubjudul:\n\n"
                ."Lalu 4-6 BLOK berbentuk:\n"
                ."Blok N — <Tema Blok>\nFakta kunci: ...\nData/Statistik (jika relevan): ...\n"
                ."Kutipan singkat (1 kalimat): ...\n\n"
                ."Akhiri dengan 'Ajakan Aksi:' satu kalimat. Bahasa singkat & visual.",

            'mindmap' => $base."\n\nTugas: buat MIND MAP berbentuk teks indentasi (tree). "
                ."Aturan format:\n"
                ."- Topik utama di baris pertama tanpa indentasi.\n"
                ."- Sub-cabang utama gunakan indentasi 2 spasi diawali '- '.\n"
                ."- Sub-sub cabang indentasi 4 spasi diawali '- '.\n"
                ."- Maksimal 3 level kedalaman, 4-6 cabang utama, 2-4 cabang anak per cabang.\n"
                ."Pastikan struktur logis (Definisi → Prinsip → Contoh → Aksi).",

            'flashcards' => $base."\n\nTugas: buat 10 KARTU FLASHCARD untuk hafalan istilah/konsep. "
                ."Format setiap kartu (pisahkan dengan baris kosong):\n"
                ."Kartu N\nDepan: <pertanyaan/istilah singkat>\nBelakang: <jawaban/penjelasan 1-2 kalimat>\n\n"
                ."Variasikan tingkat kesulitan dari mudah ke menengah.",

            'lesson_plan' => $base."\n\nTugas: susun RENCANA PEMBELAJARAN (RPP ringkas) untuk 1 sesi 2x45 menit. "
                ."Struktur wajib:\n"
                ."A. Tujuan Pembelajaran (3 poin)\n"
                ."B. Materi Pokok (ringkas)\n"
                ."C. Kegiatan Pembelajaran:\n"
                ."   1) Pendahuluan (10 menit) - apersepsi & motivasi\n"
                ."   2) Inti (60 menit) - eksplorasi, elaborasi, konfirmasi\n"
                ."   3) Penutup (20 menit) - kesimpulan & refleksi\n"
                ."D. Media & Sumber Belajar\n"
                ."E. Asesmen / Penilaian (afektif, kognitif, psikomotor)",

            default => $base."\n\nTugas: tulis MATERI PEMBELAJARAN LENGKAP dengan struktur:\n"
                ."1) Pengantar (mengapa topik ini penting bagi siswa);\n"
                ."2) Konsep Kunci (minimal 3 sub-konsep, jelaskan tiap konsep dalam 1-2 paragraf);\n"
                ."3) Refleksi Nilai & Etika ekoteologi;\n"
                ."4) Studi Kasus Indonesia (peristiwa/konteks lokal yang relevan);\n"
                ."5) Pertanyaan Reflektif (4-5 pertanyaan terbuka).\n"
                ."Gunakan bahasa Indonesia baku yang ramah pelajar.",
        };

        $raw = $ai->generateText($prompt, $system);
        // Bersihkan simbol markdown agar konsisten dengan tampilan whitespace-pre.
        $clean = preg_replace('/(\*\*|__|\*|#+)/', '', (string) $raw);
        return trim($clean);
    }


    public function storeMaterial(Request $request): RedirectResponse
    {
        $allowedFormats = array_keys(Material::formats());

        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'topic'           => 'required|string|max:255',
            'level'           => 'required|in:SD,SMP,SMA,Umum',
            'content'         => 'required|string',
            'format'          => 'nullable|string|in:'.implode(',', $allowedFormats),
            'custom_prompt'   => 'nullable|string|max:2000',
            'outputs'         => 'nullable|array',
            'outputs.*.format'  => 'required_with:outputs|string|in:'.implode(',', $allowedFormats),
            'outputs.*.label'   => 'nullable|string|max:60',
            'outputs.*.content' => 'required_with:outputs|string',
            'classroom_id'    => 'nullable|integer|exists:classrooms,id',
            'meeting_number'  => 'nullable|integer|min:1|max:9999',
            'is_published'    => 'sometimes|boolean',
        ]);

        $classroomId = ! empty($data['classroom_id']) ? (int) $data['classroom_id'] : null;
        if ($classroomId) {
            $own = Classroom::where('id', $classroomId)->where('teacher_id', Auth::id())->exists();
            if (! $own) abort(403, 'Kelas bukan milik Anda.');
        }

        // Auto-iterate kalau guru tidak isi nomor pertemuan.
        $meeting = $data['meeting_number'] ?? null;
        if (! $meeting) {
            $meeting = Material::nextMeetingNumber(Auth::id(), $classroomId);
        }

        $primaryFormat = $data['format'] ?? 'standard';
        $outputs = $this->sanitizeOutputs($data['outputs'] ?? null, $primaryFormat, (string) $data['content']);

        $material = Material::create([
            'teacher_id'     => Auth::id(),
            'classroom_id'   => $classroomId,
            'title'          => $data['title'],
            'topic'          => $data['topic'],
            'level'          => $data['level'],
            'format'         => $primaryFormat,
            'content'        => $data['content'],
            'custom_prompt'  => $data['custom_prompt'] ?? null,
            'outputs'        => $outputs,
            'meeting_number' => $meeting,
            'is_published'   => (bool) $request->boolean('is_published', true),
        ]);

        return redirect()->route('teacher.materials.show', $material)
            ->with('success', 'Materi pertemuan ke-'.$meeting.' berhasil disimpan.');
    }

    /**
     * Normalisasi & filter outputs JSON dari form. Memastikan format utama
     * tersinkron dengan kolom `content` agar tidak ada duplikasi/ambigu.
     */
    protected function sanitizeOutputs(?array $raw, string $primaryFormat, string $primaryContent): array
    {
        $allowed = array_keys(Material::formats());
        $clean = [];
        $seen = [];

        // Sertakan format utama lebih dulu.
        if (trim($primaryContent) !== '') {
            $clean[] = [
                'format'  => $primaryFormat,
                'label'   => Material::formatLabel($primaryFormat),
                'content' => $primaryContent,
            ];
            $seen[$primaryFormat] = true;
        }

        foreach ((array) $raw as $row) {
            $fmt = (string) ($row['format'] ?? '');
            $txt = trim((string) ($row['content'] ?? ''));
            if (! in_array($fmt, $allowed, true) || $txt === '' || isset($seen[$fmt])) continue;
            $clean[] = [
                'format'  => $fmt,
                'label'   => trim((string) ($row['label'] ?? Material::formatLabel($fmt))) ?: Material::formatLabel($fmt),
                'content' => $txt,
            ];
            $seen[$fmt] = true;
        }

        return $clean;
    }



    public function showMaterial(Material $material): View
    {
        $this->authorizeOwnership($material);
        $material->load([
            'questions' => fn ($q) => $q->orderBy('position')->orderBy('id'),
            'classroom', 'exams', 'discussions.replies.user', 'discussions.user',
        ]);
        $classrooms = Classroom::where('teacher_id', Auth::id())->orderBy('name')->get();
        return view('teacher.material-show', compact('material', 'classrooms'));
    }

    public function editMaterial(Material $material): View
    {
        $this->authorizeOwnership($material);
        $classrooms = Classroom::where('teacher_id', Auth::id())->orderBy('name')->get();
        return view('teacher.material-form', ['material' => $material, 'preview' => null, 'classrooms' => $classrooms]);
    }

    public function updateMaterial(Request $request, Material $material): RedirectResponse
    {
        $this->authorizeOwnership($material);
        $allowedFormats = array_keys(Material::formats());

        $data = $request->validate([
            'title'             => 'required|string|max:255',
            'topic'             => 'required|string|max:255',
            'level'             => 'required|in:SD,SMP,SMA,Umum',
            'content'           => 'required|string',
            'format'            => 'nullable|string|in:'.implode(',', $allowedFormats),
            'custom_prompt'     => 'nullable|string|max:2000',
            'outputs'           => 'nullable|array',
            'outputs.*.format'  => 'required_with:outputs|string|in:'.implode(',', $allowedFormats),
            'outputs.*.label'   => 'nullable|string|max:60',
            'outputs.*.content' => 'required_with:outputs|string',
            'classroom_id'      => 'nullable|integer|exists:classrooms,id',
            'meeting_number'    => 'nullable|integer|min:1|max:9999',
            'is_published'      => 'sometimes|boolean',
        ]);
        if (! empty($data['classroom_id'])) {
            $own = Classroom::where('id', $data['classroom_id'])->where('teacher_id', Auth::id())->exists();
            if (! $own) abort(403);
        }
        $data['is_published'] = (bool) $request->boolean('is_published', true);

        // Pastikan meeting_number selalu masuk (boleh null = "tanpa nomor pertemuan").
        $data['meeting_number'] = $data['meeting_number'] ?? null;

        // Format & outputs hanya diperbarui jika dikirim oleh form (form sidebar
        // sederhana mungkin tidak mengirim keduanya — biarkan apa adanya).
        if ($request->filled('format')) {
            $primaryFormat = $data['format'];
        } else {
            $primaryFormat = $material->format ?: 'standard';
        }
        $data['format'] = $primaryFormat;

        if ($request->has('outputs')) {
            $data['outputs'] = $this->sanitizeOutputs($data['outputs'] ?? null, $primaryFormat, (string) $data['content']);
        } else {
            // Sinkronkan ulang konten utama ke entri outputs format primer agar tetap konsisten.
            $existing = (array) ($material->outputs ?? []);
            $synced = [];
            $seen = [];
            $synced[] = [
                'format'  => $primaryFormat,
                'label'   => Material::formatLabel($primaryFormat),
                'content' => (string) $data['content'],
            ];
            $seen[$primaryFormat] = true;
            foreach ($existing as $row) {
                $fmt = (string) ($row['format'] ?? '');
                if ($fmt === '' || isset($seen[$fmt])) continue;
                $synced[] = [
                    'format'  => $fmt,
                    'label'   => (string) ($row['label'] ?? Material::formatLabel($fmt)),
                    'content' => (string) ($row['content'] ?? ''),
                ];
                $seen[$fmt] = true;
            }
            $data['outputs'] = $synced;
        }

        if (! $request->filled('custom_prompt') && ! $request->has('custom_prompt')) {
            unset($data['custom_prompt']); // jangan paksa null kalau form tidak kirim
        }

        $material->update($data);
        return redirect()->route('teacher.materials.show', $material)->with('success', 'Materi berhasil diperbarui.');
    }



    public function destroyMaterial(Material $material): RedirectResponse
    {
        $this->authorizeOwnership($material);
        $material->delete();
        return redirect()->route('teacher.index')->with('success', 'Materi berhasil dihapus.');
    }

    /**
     * Download materi sebagai PDF.
     */
    public function downloadMaterialPdf(Material $material): Response
    {
        $this->authorizeOwnership($material);
        $material->load(['teacher', 'classroom']);

        $pdf = Pdf::loadView('teacher.material-pdf', ['material' => $material])
            ->setPaper('a4', 'portrait');

        $filename = 'Materi-'.str()->slug($material->title).'-'.now()->format('Ymd').'.pdf';
        return $pdf->download($filename);
    }

    /**
     * Unduh PPTX asli (PowerPoint). Slide diparse dari output format
     * 'slides' dan dilengkapi gambar otomatis dari LoremFlickr/Picsum.
     */
    public function downloadSlidesPptx(Material $material, MaterialExportService $exporter): BinaryFileResponse
    {
        $this->authorizeOwnership($material);
        $material->load(['teacher', 'classroom']);

        try {
            $path = $exporter->buildPptx($material);
        } catch (\Throwable $e) {
            abort(500, 'Gagal membuat PPTX: '.$e->getMessage());
        }

        $filename = 'Slide-'.str()->slug($material->title).'-'.now()->format('Ymd').'.pptx';
        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Unduh PDF bergaya slide (1 halaman per slide) — alternatif PPTX
     * untuk yang tidak punya PowerPoint. Sertakan gambar di tiap slide.
     */
    public function downloadSlidesPdf(Material $material, MaterialExportService $exporter): Response
    {
        $this->authorizeOwnership($material);
        $material->load(['teacher', 'classroom']);

        $raw = $exporter->findOutput($material, 'slides') ?: $exporter->findOutput($material, 'standard') ?: '';
        $slides = $exporter->parseSlides($raw);

        if (empty($slides)) {
            $slides = [[
                'title' => $material->title,
                'bullets' => [$material->topic, 'Tingkat: '.$material->level],
                'notes' => '',
            ]];
        }

        // Embed gambar sebagai base64 agar dompdf bisa memuat tanpa akses jaringan.
        $imageData = [];
        foreach ($slides as $i => $slide) {
            $kw = $slide['title'].' '.$material->topic.' '.$material->level;
            $cacheKey = 'm'.$material->id.'-pdfs-'.$i.'-'.\Illuminate\Support\Str::slug(\Illuminate\Support\Str::limit($slide['title'], 40, ''));
            $path = $exporter->fetchImage($kw, $cacheKey);
            $imageData[$i] = $path && is_file($path)
                ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($path))
                : null;
        }

        $pdf = Pdf::loadView('teacher.material-slides-pdf', [
            'material'  => $material,
            'slides'    => $slides,
            'imageData' => $imageData,
        ])->setPaper('a4', 'landscape');

        $filename = 'Slide-PDF-'.str()->slug($material->title).'-'.now()->format('Ymd').'.pdf';
        return $pdf->download($filename);
    }

    /**
     * Unduh PDF infografis 1 halaman dengan blok-blok visual & gambar.
     */
    public function downloadInfographicPdf(Material $material, MaterialExportService $exporter)
    {

        $this->authorizeOwnership($material);
        $material->load(['teacher', 'classroom']);

        $raw = $exporter->findOutput($material, 'infographic');
        if (! $raw) {
            return back()->with('error', 'Materi ini belum punya format Infografis. Generate format Infografis dulu di halaman edit.');
        }

        $info = $exporter->parseInfographic($raw);

        // Gambar header + tiap blok
        $headerKw = ($info['title'] ?: $material->title).' '.$material->topic;
        $headerKey = 'm'.$material->id.'-info-header';
        $headerPath = $exporter->fetchImage($headerKw, $headerKey);
        $headerData = $headerPath && is_file($headerPath)
            ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($headerPath))
            : null;

        $blockImages = [];
        foreach ($info['blocks'] as $i => $block) {
            $kw = $block['title'].' '.$material->topic;
            $key = 'm'.$material->id.'-info-b'.$i;
            $path = $exporter->fetchImage($kw, $key);
            $blockImages[$i] = $path && is_file($path)
                ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($path))
                : null;
        }

        $pdf = Pdf::loadView('teacher.material-infographic-pdf', [
            'material'    => $material,
            'info'        => $info,
            'headerData'  => $headerData,
            'blockImages' => $blockImages,
        ])->setPaper('a4', 'portrait');

        $filename = 'Infografis-'.str()->slug($material->title).'-'.now()->format('Ymd').'.pdf';
        return $pdf->download($filename);
    }


    /**
     * Generate soal AI — bisa pilih tipe: essay | mcq | mixed.
     */
    public function generateQuestions(Request $request, Material $material, AIService $ai): RedirectResponse
    {
        $this->authorizeOwnership($material);
        $data = $request->validate([
            'jumlah' => 'required|integer|min:1|max:15',
            'kind'   => 'required|in:essay,mcq,mixed',
        ]);

        $kind = $data['kind'];
        $jumlah = (int) $data['jumlah'];

        $system = 'Anda penyusun soal berbahasa Indonesia bertema ekoteologi. Selalu balas JSON valid tanpa teks pembuka/penutup.';

        if ($kind === 'essay') {
            $prompt = "Berdasarkan materi berikut, buat tepat {$jumlah} soal esai dalam bahasa Indonesia.\n\n"
                    ."Judul: {$material->title}\nTopik: {$material->topic}\nTingkat: {$material->level}\n\n"
                    .$material->content."\n\n"
                    .'Skema JSON: { "questions": [ { "type": "essay", "prompt_text": "...", "rubric": "..." } ] }';
        } elseif ($kind === 'mcq') {
            $prompt = "Berdasarkan materi berikut, buat tepat {$jumlah} soal pilihan ganda (4 opsi A-D) dalam bahasa Indonesia.\n\n"
                    ."Judul: {$material->title}\nTopik: {$material->topic}\nTingkat: {$material->level}\n\n"
                    .$material->content."\n\n"
                    .'Setiap soal harus punya 4 opsi (A,B,C,D) dan satu kunci jawaban yang benar.'."\n"
                    .'Skema JSON: { "questions": [ { "type":"mcq", "prompt_text":"...", '
                    .'"options":[ {"key":"A","text":"..."},{"key":"B","text":"..."},{"key":"C","text":"..."},{"key":"D","text":"..."} ], '
                    .'"correct_option":"A" } ] }';
        } else {
            $half = (int) max(1, floor($jumlah / 2));
            $essay = $jumlah - $half;
            $prompt = "Berdasarkan materi berikut, buat campuran soal: {$half} pilihan ganda (4 opsi) DAN {$essay} esai dalam bahasa Indonesia.\n\n"
                    ."Judul: {$material->title}\nTopik: {$material->topic}\nTingkat: {$material->level}\n\n"
                    .$material->content."\n\n"
                    .'Skema JSON: { "questions": [ { "type":"mcq", "prompt_text":"...", '
                    .'"options":[{"key":"A","text":"..."},{"key":"B","text":"..."},{"key":"C","text":"..."},{"key":"D","text":"..."}], '
                    .'"correct_option":"A" }, { "type":"essay", "prompt_text":"...", "rubric":"..." } ] }';
        }

        try {
            $items = $ai->generateJson($prompt, $system)['questions'] ?? [];
        } catch (\Throwable $e) {
            return back()->with('error', 'AI gagal: '.$e->getMessage());
        }

        if (empty($items)) return back()->with('error', 'AI tidak menghasilkan soal.');

        $startPos = (int) Question::where('material_id', $material->id)->max('position') + 1;
        $created = 0;

        foreach ($items as $item) {
            $type = ($item['type'] ?? 'essay') === 'mcq' ? 'mcq' : 'essay';
            $prompt_text = trim((string) ($item['prompt_text'] ?? ''));
            if ($prompt_text === '') continue;

            if ($type === 'mcq') {
                $opts = $this->normalizeAiOptions($item['options'] ?? []);
                $correct = strtoupper((string) ($item['correct_option'] ?? ''));
                if (count($opts) < 2 || $correct === '') continue;
                // Validasi correct ada di opsi
                $keys = array_column($opts, 'key');
                if (! in_array($correct, $keys, true)) {
                    $correct = $keys[0];
                }
                Question::create([
                    'material_id'    => $material->id,
                    'prompt_text'    => $prompt_text,
                    'type'           => 'mcq',
                    'max_score'      => 100,
                    'rubric'         => null,
                    'options'        => $opts,
                    'correct_option' => $correct,
                    'position'       => $startPos++,
                ]);
            } else {
                Question::create([
                    'material_id' => $material->id,
                    'prompt_text' => $prompt_text,
                    'type'        => 'essay',
                    'max_score'   => 100,
                    'rubric'      => isset($item['rubric']) ? trim((string) $item['rubric']) : null,
                    'position'    => $startPos++,
                ]);
            }
            $created++;
        }

        if ($created === 0) return back()->with('error', 'Format soal AI tidak valid.');

        return back()->with('success', $created.' soal berhasil dibuat.');
    }

    /** Tambah soal manual (essay atau MCQ). */
    public function storeQuestion(Request $request, Material $material): RedirectResponse
    {
        $this->authorizeOwnership($material);

        $type = $request->input('type', 'essay');
        $rules = [
            'type'        => 'required|in:essay,mcq',
            'prompt_text' => 'required|string|max:2000',
            'max_score'   => 'nullable|integer|min:1|max:100',
        ];

        if ($type === 'mcq') {
            $rules += [
                'options'        => 'required|array|min:2|max:6',
                'options.*'      => 'required|string|max:500',
                'correct_index'  => 'required|integer|min:0',
            ];
        } else {
            $rules['rubric'] = 'nullable|string|max:2000';
        }

        $data = $request->validate($rules);

        $position = (int) Question::where('material_id', $material->id)->max('position') + 1;

        if ($type === 'mcq') {
            $opts = [];
            foreach ($data['options'] as $i => $text) {
                $text = trim($text);
                if ($text === '') continue;
                $opts[] = ['key' => chr(65 + count($opts)), 'text' => $text];
            }
            if (count($opts) < 2) {
                return back()->with('error', 'Pilihan ganda butuh minimal 2 opsi.');
            }
            $correctIdx = min((int) $data['correct_index'], count($opts) - 1);
            $correctKey = $opts[$correctIdx]['key'];

            Question::create([
                'material_id'    => $material->id,
                'prompt_text'    => $data['prompt_text'],
                'type'           => 'mcq',
                'max_score'      => $data['max_score'] ?? 100,
                'options'        => $opts,
                'correct_option' => $correctKey,
                'position'       => $position,
            ]);
        } else {
            Question::create([
                'material_id' => $material->id,
                'prompt_text' => $data['prompt_text'],
                'rubric'      => $data['rubric'] ?? null,
                'max_score'   => $data['max_score'] ?? 100,
                'type'        => 'essay',
                'position'    => $position,
            ]);
        }

        return back()->with('success', 'Soal ditambahkan.');
    }

    public function updateQuestion(Request $request, Question $question): RedirectResponse
    {
        $this->authorizeOwnership($question->material);

        $rules = [
            'prompt_text' => 'required|string|max:2000',
            'max_score'   => 'nullable|integer|min:1|max:100',
        ];
        if ($question->type === 'mcq') {
            $rules += [
                'options'       => 'required|array|min:2|max:6',
                'options.*'     => 'required|string|max:500',
                'correct_index' => 'required|integer|min:0',
            ];
        } else {
            $rules['rubric'] = 'nullable|string|max:2000';
        }

        $data = $request->validate($rules);

        if ($question->type === 'mcq') {
            $opts = [];
            foreach ($data['options'] as $text) {
                $text = trim($text);
                if ($text === '') continue;
                $opts[] = ['key' => chr(65 + count($opts)), 'text' => $text];
            }
            if (count($opts) < 2) return back()->with('error', 'Pilihan ganda butuh minimal 2 opsi.');
            $correctIdx = min((int) $data['correct_index'], count($opts) - 1);

            $question->update([
                'prompt_text'    => $data['prompt_text'],
                'max_score'      => $data['max_score'] ?? $question->max_score,
                'options'        => $opts,
                'correct_option' => $opts[$correctIdx]['key'],
            ]);
        } else {
            $question->update([
                'prompt_text' => $data['prompt_text'],
                'rubric'      => $data['rubric'] ?? null,
                'max_score'   => $data['max_score'] ?? $question->max_score,
            ]);
        }

        return back()->with('success', 'Soal diperbarui.');
    }

    public function destroyQuestion(Question $question): RedirectResponse
    {
        $this->authorizeOwnership($question->material);
        $question->delete();
        return back()->with('success', 'Soal dihapus.');
    }

    public function submissions(Material $material): View
    {
        $this->authorizeOwnership($material);
        $submissions = \App\Models\Submission::with(['user', 'question'])
            ->whereHas('question', fn ($q) => $q->where('material_id', $material->id))
            ->latest()->paginate(15);
        return view('teacher.submissions', compact('material', 'submissions'));
    }

    protected function authorizeOwnership(Material $material): void
    {
        abort_unless($material->teacher_id === Auth::id(), 403, 'Materi ini bukan milik Anda.');
    }

    /**
     * Normalisasi opsi MCQ dari output AI menjadi [{key,text}, ...].
     */
    protected function normalizeAiOptions(mixed $raw): array
    {
        if (! is_array($raw)) return [];
        $out = [];
        foreach ($raw as $i => $opt) {
            if (is_array($opt)) {
                $key = isset($opt['key']) && $opt['key'] !== '' ? strtoupper((string) $opt['key']) : chr(65 + count($out));
                $text = trim((string) ($opt['text'] ?? ''));
            } else {
                $key = chr(65 + count($out));
                $text = trim((string) $opt);
            }
            if ($text === '') continue;
            $out[] = ['key' => $key, 'text' => $text];
        }
        return $out;
    }
}

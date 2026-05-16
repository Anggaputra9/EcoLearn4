<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Material;
use App\Models\Question;
use App\Services\AIService;
use Barryvdh\DomPDF\Facade\Pdf;
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
     * Endpoint AJAX dipakai modal "Buat Materi" → mengembalikan JSON preview
     * sehingga guru bisa meninjau dan menyimpan tanpa pindah halaman.
     */
    public function generateMaterialAjax(Request $request, AIService $ai): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'topic' => 'required|string|max:255',
            'level' => 'required|in:SD,SMP,SMA,Umum',
        ]);

        $system = 'Anda adalah penulis materi pembelajaran berbahasa Indonesia '
                .'dengan spesialisasi ekoteologi (perpaduan teologi & ekologi). '
                .'Hasilkan materi yang mendidik, akurat, ramah pelajar, dan inklusif.';

        $prompt = "Buatkan materi pembelajaran lengkap berbahasa Indonesia tentang topik ekoteologi: \"{$data['topic']}\".\n"
                ."Sasaran pembaca: tingkat {$data['level']}.\n"
                ."Struktur: 1) Pengantar; 2) Konsep kunci (3+); 3) Refleksi nilai & etika; 4) Studi kasus Indonesia; 5) Pertanyaan reflektif.\n"
                ."Tanpa simbol markdown (**, *, #), tanpa emoji.";

        try {
            $content = preg_replace('/(\*\*|\*|#+)/', '', $ai->generateText($prompt, $system));
            return response()->json(['ok' => true, 'content' => trim($content)]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function storeMaterial(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'topic'          => 'required|string|max:255',
            'level'          => 'required|in:SD,SMP,SMA,Umum',
            'content'        => 'required|string',
            'classroom_id'   => 'nullable|integer|exists:classrooms,id',
            'meeting_number' => 'nullable|integer|min:1|max:9999',
            'is_published'   => 'sometimes|boolean',
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

        $material = Material::create([
            'teacher_id'     => Auth::id(),
            'classroom_id'   => $classroomId,
            'title'          => $data['title'],
            'topic'          => $data['topic'],
            'level'          => $data['level'],
            'content'        => $data['content'],
            'meeting_number' => $meeting,
            'is_published'   => (bool) $request->boolean('is_published', true),
        ]);

        return redirect()->route('teacher.materials.show', $material)
            ->with('success', 'Materi pertemuan ke-'.$meeting.' berhasil disimpan.');
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
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'topic'          => 'required|string|max:255',
            'level'          => 'required|in:SD,SMP,SMA,Umum',
            'content'        => 'required|string',
            'classroom_id'   => 'nullable|integer|exists:classrooms,id',
            'meeting_number' => 'nullable|integer|min:1|max:9999',
            'is_published'   => 'sometimes|boolean',
        ]);
        if (! empty($data['classroom_id'])) {
            $own = Classroom::where('id', $data['classroom_id'])->where('teacher_id', Auth::id())->exists();
            if (! $own) abort(403);
        }
        $data['is_published'] = (bool) $request->boolean('is_published', true);

        // Pastikan meeting_number selalu masuk (boleh null = "tanpa nomor pertemuan").
        $data['meeting_number'] = $data['meeting_number'] ?? null;

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

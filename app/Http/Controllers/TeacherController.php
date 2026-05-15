<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Material;
use App\Models\Question;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $classrooms = Classroom::where('teacher_id', Auth::id())->orderBy('name')->get();

        return view('teacher.index', compact('materials', 'q', 'level', 'classrooms', 'classroomId'));
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
            'title'        => 'required|string|max:255',
            'topic'        => 'required|string|max:255',
            'level'        => 'required|in:SD,SMP,SMA,Umum',
            'content'      => 'required|string',
            'classroom_id' => 'nullable|integer|exists:classrooms,id',
            'is_published' => 'sometimes|boolean',
        ]);

        // Pastikan classroom milik guru ini kalau diisi
        if (! empty($data['classroom_id'])) {
            $own = Classroom::where('id', $data['classroom_id'])->where('teacher_id', Auth::id())->exists();
            if (! $own) abort(403, 'Kelas bukan milik Anda.');
        }

        $material = Material::create($data + [
            'teacher_id'   => Auth::id(),
            'is_published' => (bool) $request->boolean('is_published', true),
        ]);

        return redirect()->route('teacher.materials.show', $material)->with('success', 'Materi berhasil disimpan.');
    }

    public function showMaterial(Material $material): View
    {
        $this->authorizeOwnership($material);
        $material->load(['questions', 'classroom', 'exams', 'discussions.replies.user', 'discussions.user']);
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
            'title'        => 'required|string|max:255',
            'topic'        => 'required|string|max:255',
            'level'        => 'required|in:SD,SMP,SMA,Umum',
            'content'      => 'required|string',
            'classroom_id' => 'nullable|integer|exists:classrooms,id',
            'is_published' => 'sometimes|boolean',
        ]);
        if (! empty($data['classroom_id'])) {
            $own = Classroom::where('id', $data['classroom_id'])->where('teacher_id', Auth::id())->exists();
            if (! $own) abort(403);
        }
        $data['is_published'] = (bool) $request->boolean('is_published', true);

        $material->update($data);
        return redirect()->route('teacher.materials.show', $material)->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroyMaterial(Material $material): RedirectResponse
    {
        $this->authorizeOwnership($material);
        $material->delete();
        return redirect()->route('teacher.index')->with('success', 'Materi berhasil dihapus.');
    }

    public function generateQuestions(Request $request, Material $material, AIService $ai): RedirectResponse
    {
        $this->authorizeOwnership($material);
        $data = $request->validate(['jumlah' => 'required|integer|min:1|max:10']);

        $system = 'Anda penyusun soal esai berbahasa Indonesia bertema ekoteologi. Selalu balas JSON valid.';
        $prompt = "Berdasarkan materi berikut, buat tepat {$data['jumlah']} soal esai dalam bahasa Indonesia.\n\n"
                ."Judul: {$material->title}\nTopik: {$material->topic}\nTingkat: {$material->level}\n\n"
                .$material->content."\n\n"
                .'Skema JSON: { "questions": [ { "prompt_text": "...", "rubric": "..." } ] }';

        try {
            $items = $ai->generateJson($prompt, $system)['questions'] ?? [];
        } catch (\Throwable $e) {
            return back()->with('error', 'AI gagal: '.$e->getMessage());
        }

        if (empty($items)) return back()->with('error', 'AI tidak menghasilkan soal.');

        foreach ($items as $item) {
            if (empty($item['prompt_text'])) continue;
            Question::create([
                'material_id' => $material->id,
                'prompt_text' => trim($item['prompt_text']),
                'type' => 'essay', 'max_score' => 100,
                'rubric' => isset($item['rubric']) ? trim($item['rubric']) : null,
            ]);
        }

        return back()->with('success', count($items).' soal esai berhasil dibuat.');
    }

    /** Buat / edit soal manual (atau meluruskan hasil AI). */
    public function storeQuestion(Request $request, Material $material): RedirectResponse
    {
        $this->authorizeOwnership($material);
        $data = $request->validate([
            'prompt_text' => 'required|string|max:2000',
            'rubric'      => 'nullable|string|max:2000',
            'max_score'   => 'nullable|integer|min:1|max:100',
        ]);
        Question::create([
            'material_id' => $material->id,
            'prompt_text' => $data['prompt_text'],
            'rubric'      => $data['rubric'] ?? null,
            'max_score'   => $data['max_score'] ?? 100,
            'type'        => 'essay',
        ]);
        return back()->with('success', 'Soal ditambahkan.');
    }

    public function updateQuestion(Request $request, Question $question): RedirectResponse
    {
        $this->authorizeOwnership($question->material);
        $data = $request->validate([
            'prompt_text' => 'required|string|max:2000',
            'rubric'      => 'nullable|string|max:2000',
            'max_score'   => 'nullable|integer|min:1|max:100',
        ]);
        $question->update($data + ['max_score' => $data['max_score'] ?? $question->max_score]);
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
}

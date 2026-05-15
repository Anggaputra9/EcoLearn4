<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Question;
use App\Models\Submission;
use App\Services\AIService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $level = $request->get('level');
        $user = Auth::user();

        // Materi: yang publik + yang ada di kelas yang diikuti
        $joinedClassroomIds = $user->classroomsJoined()->pluck('classrooms.id');

        $materials = Material::with(['teacher', 'questions', 'classroom'])
            ->where('is_published', true)
            ->where(function ($w) use ($joinedClassroomIds) {
                $w->whereNull('classroom_id')
                  ->orWhereIn('classroom_id', $joinedClassroomIds);
            })
            ->when($q, fn ($qq) => $qq->where(function ($w) use ($q) {
                $w->where('title', 'like', "%$q%")->orWhere('topic', 'like', "%$q%");
            }))
            ->when($level, fn ($qq) => $qq->where('level', $level))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('student.index', compact('materials', 'q', 'level'));
    }

    public function showMaterial(Material $material): View
    {
        $this->ensureCanAccess($material);
        $material->load(['teacher', 'questions', 'classroom', 'exams' => fn ($q) => $q->whereIn('status', ['published', 'closed'])]);

        $mySubmissions = Submission::where('user_id', Auth::id())
            ->whereIn('question_id', $material->questions->pluck('id'))
            ->whereNull('exam_attempt_id') // hanya yang non-ujian
            ->get()->keyBy('question_id');

        return view('student.material-show', compact('material', 'mySubmissions'));
    }

    public function answerForm(Question $question): View
    {
        $material = $question->material;
        $this->ensureCanAccess($material);

        $existing = Submission::where('user_id', Auth::id())
            ->where('question_id', $question->id)
            ->whereNull('exam_attempt_id')->first();
        return view('student.answer-form', compact('question', 'material', 'existing'));
    }

    public function submitAnswer(Request $request, Question $question, AIService $ai): RedirectResponse
    {
        $material = $question->material;
        $this->ensureCanAccess($material);

        $data = $request->validate(['answer_text' => 'required|string|min:20']);

        $submission = Submission::updateOrCreate(
            ['user_id' => Auth::id(), 'question_id' => $question->id, 'exam_attempt_id' => null],
            ['answer_text' => $data['answer_text'], 'status' => 'pending',
             'score' => null, 'feedback' => null, 'graded_at' => null]
        );

        try {
            $result = $this->gradeWithAi($ai, $material, $question, $data['answer_text']);
            $submission->update([
                'score' => $result['score'], 'feedback' => $result['feedback'],
                'status' => 'graded', 'graded_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $submission->update([
                'status' => 'failed',
                'feedback' => 'Koreksi otomatis gagal: '.$e->getMessage(),
            ]);
            return redirect()->route('student.materials.show', $material)
                ->with('error', 'Jawaban tersimpan, namun koreksi AI gagal. Silakan coba kirim ulang.');
        }

        return redirect()->route('student.submissions.show', $submission)
            ->with('success', 'Jawaban berhasil dikoreksi oleh AI.');
    }

    public function showSubmission(Submission $submission): View
    {
        abort_unless($submission->user_id === Auth::id(), 403);
        $submission->load(['question.material']);
        return view('student.submission-show', compact('submission'));
    }

    protected function gradeWithAi(AIService $ai, Material $material, Question $question, string $answer): array
    {
        $system = 'Anda korektor esai berbahasa Indonesia yang adil, pedagogis, paham ekoteologi. '
                .'Selalu balas hanya dalam JSON valid.';

        $rubric = $question->rubric ?: 'Penilaian umum: relevansi, kedalaman analisis, penggunaan konsep ekoteologi, struktur tulisan, tata bahasa.';

        $prompt = "Konteks materi:\nJudul: {$material->title}\nTopik: {$material->topic}\nTingkat: {$material->level}\n\n"
                ."Soal: {$question->prompt_text}\n\nRubrik:\n{$rubric}\n\n"
                ."Jawaban siswa:\n\"\"\"\n{$answer}\n\"\"\"\n\n"
                ."Tugas: 1) skor 0-100; 2) feedback konstruktif 3-6 kalimat dalam Bahasa Indonesia.\n"
                .'Skema JSON: { "score": <int>, "feedback": "<string>" }';

        $json = $ai->generateJson($prompt, $system);
        $score = isset($json['score']) ? (int) $json['score'] : null;
        $feedback = isset($json['feedback']) ? trim((string) $json['feedback']) : '';

        if ($score === null || $score < 0 || $score > 100 || $feedback === '') {
            throw new \RuntimeException('Format hasil koreksi AI tidak valid.');
        }

        return ['score' => $score, 'feedback' => $feedback];
    }

    protected function ensureCanAccess(Material $material): void
    {
        abort_unless($material->is_published, 404);
        if ($material->classroom_id) {
            $isMember = $material->classroom?->members()->whereKey(Auth::id())->exists();
            abort_unless($isMember, 403, 'Materi ini hanya untuk anggota kelas.');
        }
    }
}

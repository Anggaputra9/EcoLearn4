<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Submission;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamRunController extends Controller
{
    /** Layar lobby ujian (sebelum mulai). */
    public function lobby(Exam $exam): View|RedirectResponse
    {
        $this->ensureCanAccess($exam);

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())->first();

        if ($attempt && $attempt->status === 'in_progress') {
            return redirect()->route('student.exams.run', $exam);
        }
        if ($attempt && in_array($attempt->status, ['submitted', 'disqualified', 'expired'])) {
            return redirect()->route('student.exams.result', $exam);
        }

        $exam->load('material.questions');
        return view('student.exams.lobby', compact('exam'));
    }

    /** Mulai attempt baru dan masuk ke layar mengerjakan. */
    public function start(Exam $exam): RedirectResponse
    {
        $this->ensureCanAccess($exam);

        ExamAttempt::firstOrCreate(
            ['exam_id' => $exam->id, 'user_id' => Auth::id()],
            ['started_at' => now(), 'status' => 'in_progress', 'tab_switch_count' => 0],
        );

        return redirect()->route('student.exams.run', $exam);
    }

    /** Layar mengerjakan dengan anti-cheat aktif. */
    public function run(Exam $exam): View|RedirectResponse
    {
        $this->ensureCanAccess($exam);

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())->firstOrFail();

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('student.exams.result', $exam);
        }

        // Auto-expire kalau lewat durasi
        if ($exam->duration_minutes > 0 && $attempt->started_at) {
            $deadline = $attempt->started_at->copy()->addMinutes($exam->duration_minutes);
            if (now()->greaterThanOrEqualTo($deadline)) {
                $attempt->update(['status' => 'expired', 'submitted_at' => now()]);
                $this->finalizeAttempt($attempt);
                return redirect()->route('student.exams.result', $exam);
            }
        }

        $exam->load(['material.questions' => fn ($q) => $q->orderBy('position')->orderBy('id')]);
        $questions = $exam->material->questions;
        if ($exam->shuffle_questions) {
            $questions = $questions->shuffle()->values();
        }

        $existing = Submission::where('exam_attempt_id', $attempt->id)
            ->get()->keyBy('question_id');

        $remaining = $exam->duration_minutes > 0 && $attempt->started_at
            ? max(0, now()->diffInSeconds($attempt->started_at->copy()->addMinutes($exam->duration_minutes), false))
            : 0;

        return view('student.exams.run', compact('exam', 'attempt', 'questions', 'existing', 'remaining'));
    }

    /** Simpan progres jawaban (auto-save). Mendukung essay text & MCQ pilihan. */
    public function save(Request $request, Exam $exam, Question $question): JsonResponse
    {
        $this->ensureCanAccess($exam);

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())->where('status', 'in_progress')->firstOrFail();

        $data = $request->validate([
            'answer_text'     => 'nullable|string|max:20000',
            'selected_option' => 'nullable|string|max:8',
        ]);

        $payload = [
            'user_id' => Auth::id(),
            'status'  => 'pending',
        ];
        if ($question->isMcq()) {
            $payload['selected_option'] = $data['selected_option'] ?? null;
            $payload['answer_text']     = $data['selected_option'] ?? '';
        } else {
            $payload['answer_text'] = $data['answer_text'] ?? '';
        }

        Submission::updateOrCreate(
            ['exam_attempt_id' => $attempt->id, 'question_id' => $question->id],
            $payload,
        );

        return response()->json(['ok' => true, 'saved_at' => now()->toIso8601String()]);
    }

    /** Catat kecurangan (tab switch / paste / dll). Bisa men-disqualify. */
    public function reportCheat(Request $request, Exam $exam): JsonResponse
    {
        $this->ensureCanAccess($exam);

        $event = (string) $request->input('event', 'unknown');
        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())->where('status', 'in_progress')->firstOrFail();

        $disqualified = false;
        if ($event === 'tab-switch') {
            $attempt->tab_switch_count = (int) $attempt->tab_switch_count + 1;
            if ($exam->prevent_tab_switch && $exam->max_tab_switch >= 0
                && $attempt->tab_switch_count > $exam->max_tab_switch) {
                $attempt->status = 'disqualified';
                $attempt->submitted_at = now();
                $disqualified = true;
            }
        }
        $attempt->appendCheatLog($event);
        $attempt->save();

        if ($disqualified) {
            $this->finalizeAttempt($attempt->fresh());
        }

        return response()->json([
            'ok'           => true,
            'disqualified' => $disqualified,
            'count'        => $attempt->tab_switch_count,
            'remaining'    => max(0, (int) $exam->max_tab_switch - (int) $attempt->tab_switch_count),
        ]);
    }

    /** Submit final dan trigger koreksi otomatis (jika modenya auto/hybrid). */
    public function submit(Request $request, Exam $exam, AIService $ai): RedirectResponse
    {
        $this->ensureCanAccess($exam);

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())->where('status', 'in_progress')->firstOrFail();

        // Simpan sisa jawaban dari form (essay & MCQ).
        $textAnswers = $request->input('answers', []);
        $mcqChoices  = $request->input('choices', []);

        // Map question_id => Question untuk validasi tipe
        $qMap = $exam->material->questions()->get()->keyBy('id');

        foreach ($qMap as $qid => $question) {
            if ($question->isMcq()) {
                $choice = $mcqChoices[$qid] ?? null;
                if ($choice === null || $choice === '') continue;
                Submission::updateOrCreate(
                    ['exam_attempt_id' => $attempt->id, 'question_id' => (int) $qid],
                    [
                        'user_id'         => Auth::id(),
                        'selected_option' => (string) $choice,
                        'answer_text'     => (string) $choice,
                        'status'          => 'pending',
                    ]
                );
            } else {
                $text = $textAnswers[$qid] ?? null;
                if (! is_string($text) || trim($text) === '') continue;
                Submission::updateOrCreate(
                    ['exam_attempt_id' => $attempt->id, 'question_id' => (int) $qid],
                    [
                        'user_id'     => Auth::id(),
                        'answer_text' => $text,
                        'status'      => 'pending',
                    ]
                );
            }
        }

        $attempt->update(['status' => 'submitted', 'submitted_at' => now()]);

        // Koreksi (essay AI + MCQ otomatis) berdasarkan grading_mode
        $this->finalizeAttempt($attempt->fresh(), $ai);

        return redirect()->route('student.exams.result', $exam);
    }

    public function result(Exam $exam): View
    {
        $this->ensureCanAccess($exam, allowMember: true);

        $attempt = ExamAttempt::with(['submissions.question', 'exam.material'])
            ->where('exam_id', $exam->id)
            ->where('user_id', Auth::id())->firstOrFail();

        $canSeeResult = $exam->show_result_after_submit || $attempt->result_released;

        $leaderboard = null;
        if ($exam->show_leaderboard) {
            $leaderboard = ExamAttempt::with('user')
                ->where('exam_id', $exam->id)
                ->whereIn('status', ['submitted'])
                ->whereNotNull('total_score')
                ->orderByDesc('total_score')
                ->take(20)->get();
        }

        return view('student.exams.result', compact('exam', 'attempt', 'canSeeResult', 'leaderboard'));
    }

    /* ============================================================
     * Helpers
     * ============================================================ */
    protected function ensureCanAccess(Exam $exam, bool $allowMember = false): void
    {
        if ($exam->status !== 'published' && $exam->status !== 'closed') abort(404);
        if (! $exam->isOpenNow() && ! $allowMember) abort(404, 'Ujian tidak sedang berlangsung.');

        if ($exam->classroom_id) {
            $isMember = DB::table('classroom_members')
                ->where('classroom_id', $exam->classroom_id)
                ->where('user_id', Auth::id())->exists();
            abort_unless($isMember, 403, 'Anda bukan anggota kelas ini.');
        }
    }

    /**
     * Finalisasi attempt: koreksi MCQ otomatis + (jika mode auto/hybrid) koreksi esai via AI.
     * Skor akhir = akumulasi skor seluruh soal (dipersentasekan ke 100).
     */
    protected function finalizeAttempt(ExamAttempt $attempt, ?AIService $ai = null): void
    {
        $exam = $attempt->exam;
        $material = $exam->material;
        $material->loadMissing(['questions']);

        // Koreksi MCQ — selalu otomatis (tidak butuh AI)
        $mcqQuestions = $material->questions->where('type', 'mcq');
        foreach ($mcqQuestions as $q) {
            $sub = Submission::where('exam_attempt_id', $attempt->id)
                ->where('question_id', $q->id)->first();
            if (! $sub) {
                // Buat submission kosong untuk MCQ tidak dijawab → skor 0
                $sub = Submission::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id'     => $q->id,
                    'user_id'         => $attempt->user_id,
                    'answer_text'     => '',
                    'selected_option' => null,
                    'status'          => 'pending',
                ]);
            }
            $isCorrect = $sub->selected_option !== null
                && strtoupper((string) $sub->selected_option) === strtoupper((string) $q->correct_option);

            $sub->update([
                'score'           => $isCorrect ? (int) $q->max_score : 0,
                'status'          => 'graded',
                'feedback'        => $isCorrect
                    ? 'Jawaban benar.'
                    : 'Jawaban kurang tepat. Kunci: '.($q->correct_option ?: '—'),
                'graded_at'       => now(),
                'manually_graded' => false,
            ]);
        }

        // Koreksi esai (kalau mode auto/hybrid dan AI tersedia)
        if ($ai && in_array($exam->grading_mode, ['auto_ai', 'hybrid'])) {
            foreach ($material->questions->where('type', 'essay') as $q) {
                $sub = Submission::where('exam_attempt_id', $attempt->id)
                    ->where('question_id', $q->id)->first();
                if (! $sub || trim((string) $sub->answer_text) === '') {
                    if (! $sub) {
                        Submission::create([
                            'exam_attempt_id' => $attempt->id,
                            'question_id'     => $q->id,
                            'user_id'         => $attempt->user_id,
                            'answer_text'     => '',
                            'score'           => 0,
                            'status'          => 'graded',
                            'feedback'        => 'Tidak dijawab.',
                            'graded_at'       => now(),
                        ]);
                    } else {
                        $sub->update(['score' => 0, 'status' => 'graded',
                                      'feedback' => 'Tidak dijawab.', 'graded_at' => now()]);
                    }
                    continue;
                }

                try {
                    $rubric = $q->rubric ?: 'Penilaian umum: relevansi, kedalaman analisis, struktur tulisan, tata bahasa.';
                    $prompt = "Konteks materi:\nJudul: {$material->title}\nTopik: {$material->topic}\nTingkat: {$material->level}\n\n"
                            ."Soal: {$q->prompt_text}\n\nRubrik:\n{$rubric}\n\n"
                            ."Jawaban siswa:\n\"\"\"\n{$sub->answer_text}\n\"\"\"\n\n"
                            ."Tugas: 1) skor 0-100; 2) feedback konstruktif 3-6 kalimat dalam Bahasa Indonesia.\n"
                            .'Skema JSON: { "score": <int>, "feedback": "<string>" }';
                    $sys = 'Anda korektor esai berbahasa Indonesia yang adil & pedagogis. Selalu balas hanya JSON valid.';

                    $json = $ai->generateJson($prompt, $sys);
                    $score = isset($json['score']) ? max(0, min(100, (int) $json['score'])) : null;
                    $feedback = isset($json['feedback']) ? trim((string) $json['feedback']) : '';

                    if ($score !== null && $feedback !== '') {
                        $rawScore = (int) round(($score / 100) * (int) $q->max_score);
                        $sub->update([
                            'score'           => $rawScore,
                            'feedback'        => $feedback,
                            'status'          => 'graded',
                            'graded_at'       => now(),
                            'manually_graded' => false,
                        ]);
                    } else {
                        $sub->update(['status' => 'submitted']);
                    }
                } catch (\Throwable $e) {
                    $sub->update([
                        'status'   => 'failed',
                        'feedback' => 'Koreksi otomatis gagal: '.$e->getMessage(),
                    ]);
                }
            }
        }

        // Hitung skor akumulatif: total skor dari semua soal / total max_score × 100
        $allSubs = Submission::with('question')
            ->where('exam_attempt_id', $attempt->id)
            ->whereHas('question', fn ($q) => $q->where('material_id', $material->id))
            ->get();

        $totalEarned = 0;
        $totalMax = 0;
        foreach ($material->questions as $q) {
            $totalMax += (int) $q->max_score;
            $sub = $allSubs->firstWhere('question_id', $q->id);
            if ($sub && $sub->score !== null) {
                $totalEarned += (int) $sub->score;
            }
        }

        $finalScore = $totalMax > 0 ? (int) round(($totalEarned / $totalMax) * 100) : 0;

        $attempt->update([
            'total_score'     => $finalScore,
            'max_score'       => 100,
            'result_released' => $exam->show_result_after_submit ? true : (bool) $attempt->result_released,
        ]);
    }
}

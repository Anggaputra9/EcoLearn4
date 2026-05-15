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
                return redirect()->route('student.exams.result', $exam);
            }
        }

        $exam->load('material.questions');
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

    /** Simpan progres jawaban (auto-save). */
    public function save(Request $request, Exam $exam, Question $question): JsonResponse
    {
        $this->ensureCanAccess($exam);

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())->where('status', 'in_progress')->firstOrFail();

        $data = $request->validate(['answer_text' => 'required|string|max:20000']);

        Submission::updateOrCreate(
            ['exam_attempt_id' => $attempt->id, 'question_id' => $question->id],
            ['answer_text' => $data['answer_text'], 'user_id' => Auth::id(), 'status' => 'pending'],
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
            // 0 = langsung gugur; >0 = boleh hingga max
            if ($exam->prevent_tab_switch && $exam->max_tab_switch >= 0
                && $attempt->tab_switch_count > $exam->max_tab_switch) {
                $attempt->status = 'disqualified';
                $attempt->submitted_at = now();
                $disqualified = true;
            }
        }
        $attempt->appendCheatLog($event);
        $attempt->save();

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

        // Simpan sisa jawaban dari form (kalau ada)
        $payload = $request->input('answers', []);
        foreach ($payload as $questionId => $text) {
            if (! is_string($text) || trim($text) === '') continue;
            Submission::updateOrCreate(
                ['exam_attempt_id' => $attempt->id, 'question_id' => (int) $questionId],
                ['answer_text' => $text, 'user_id' => Auth::id(), 'status' => 'pending'],
            );
        }

        $attempt->update(['status' => 'submitted', 'submitted_at' => now()]);

        // Koreksi
        if (in_array($exam->grading_mode, ['auto_ai', 'hybrid'])) {
            $this->autoGrade($attempt, $ai);
        }

        return redirect()->route('student.exams.result', $exam);
    }

    public function result(Exam $exam): View
    {
        $this->ensureCanAccess($exam, allowMember: true);

        $attempt = ExamAttempt::with(['submissions.question', 'exam.material'])
            ->where('exam_id', $exam->id)
            ->where('user_id', Auth::id())->firstOrFail();

        // Apakah hasil boleh tampil?
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

        // Jika ujian terikat ke kelas, siswa harus member kelas itu
        if ($exam->classroom_id) {
            $isMember = DB::table('classroom_members')
                ->where('classroom_id', $exam->classroom_id)
                ->where('user_id', Auth::id())->exists();
            abort_unless($isMember, 403, 'Anda bukan anggota kelas ini.');
        }
    }

    protected function autoGrade(ExamAttempt $attempt, AIService $ai): void
    {
        $exam = $attempt->exam;
        $material = $exam->material;
        $total = 0;
        $maxTotal = 0;

        foreach ($attempt->submissions()->with('question')->get() as $sub) {
            if (! $sub->question) continue;
            $maxTotal += (int) $sub->question->max_score;

            try {
                $rubric = $sub->question->rubric ?: 'Penilaian umum: relevansi, kedalaman analisis, struktur tulisan, tata bahasa.';
                $prompt = "Konteks materi:\nJudul: {$material->title}\nTopik: {$material->topic}\nTingkat: {$material->level}\n\n"
                        ."Soal: {$sub->question->prompt_text}\n\nRubrik:\n{$rubric}\n\n"
                        ."Jawaban siswa:\n\"\"\"\n{$sub->answer_text}\n\"\"\"\n\n"
                        ."Tugas: 1) skor 0-100 (integer); 2) feedback konstruktif 3-6 kalimat dalam Bahasa Indonesia.\n"
                        .'Skema JSON: { "score": <int>, "feedback": "<string>" }';
                $sys = 'Anda korektor esai berbahasa Indonesia yang adil & pedagogis. Selalu balas hanya JSON valid.';

                $json = $ai->generateJson($prompt, $sys);
                $score = isset($json['score']) ? max(0, min(100, (int) $json['score'])) : null;
                $feedback = isset($json['feedback']) ? trim((string) $json['feedback']) : '';

                if ($score !== null && $feedback !== '') {
                    $sub->update([
                        'score' => $score,
                        'feedback' => $feedback,
                        'status' => 'graded',
                        'graded_at' => now(),
                        'manually_graded' => false,
                    ]);
                    $total += $score;
                } else {
                    $sub->update(['status' => 'submitted']);
                }
            } catch (\Throwable $e) {
                $sub->update([
                    'status' => 'failed',
                    'feedback' => 'Koreksi otomatis gagal: '.$e->getMessage(),
                ]);
            }
        }

        $attempt->update([
            'total_score' => min(100, $total > 0 ? (int) round($total / max(1, $attempt->submissions()->count())) : 0),
            'max_score'   => 100,
            'result_released' => $exam->show_result_after_submit,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Submission;
use App\Services\AIService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    /** Layar koreksi semua submission untuk satu attempt. */
    public function reviewAttempt(ExamAttempt $attempt): View
    {
        $this->authorizeAttempt($attempt);
        $attempt->load(['user', 'exam.material', 'submissions.question']);
        return view('teacher.exams.review-attempt', compact('attempt'));
    }

    /** Update skor & feedback secara manual. */
    public function manualGrade(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorizeSubmission($submission);
        $data = $request->validate([
            'score'    => 'required|integer|min:0|max:100',
            'feedback' => 'required|string|max:4000',
        ]);

        $submission->update([
            'score'           => $data['score'],
            'feedback'        => $data['feedback'],
            'status'          => 'graded',
            'graded_at'       => now(),
            'manually_graded' => true,
            'graded_by'       => Auth::id(),
        ]);

        $this->recomputeAttemptTotal($submission->exam_attempt_id);

        return back()->with('success', 'Nilai manual disimpan.');
    }

    /** Jalankan koreksi AI untuk satu submission saja. */
    public function aiGrade(Submission $submission, AIService $ai): RedirectResponse
    {
        $this->authorizeSubmission($submission);
        if (! $submission->question || ! $submission->question->material) {
            return back()->with('error', 'Konteks soal/material tidak ditemukan.');
        }

        $material = $submission->question->material;
        $rubric = $submission->question->rubric ?: 'Penilaian umum: relevansi, kedalaman analisis, struktur tulisan, tata bahasa.';
        $prompt = "Konteks materi:\nJudul: {$material->title}\nTopik: {$material->topic}\nTingkat: {$material->level}\n\n"
                ."Soal: {$submission->question->prompt_text}\n\nRubrik:\n{$rubric}\n\n"
                ."Jawaban siswa:\n\"\"\"\n{$submission->answer_text}\n\"\"\"\n\n"
                ."Tugas: skor 0-100 dan feedback 3-6 kalimat. Skema JSON: { \"score\": <int>, \"feedback\": \"<string>\" }";
        $sys = 'Anda korektor esai berbahasa Indonesia yang adil & pedagogis. Selalu balas hanya JSON valid.';

        try {
            $json = $ai->generateJson($prompt, $sys);
            $score = max(0, min(100, (int) ($json['score'] ?? 0)));
            $feedback = trim((string) ($json['feedback'] ?? ''));
            if ($feedback === '') throw new \RuntimeException('AI tidak menghasilkan feedback.');

            $submission->update([
                'score'           => $score,
                'feedback'        => $feedback,
                'status'          => 'graded',
                'graded_at'       => now(),
                'manually_graded' => false,
                'graded_by'       => Auth::id(),
            ]);
            $this->recomputeAttemptTotal($submission->exam_attempt_id);
            return back()->with('success', 'Koreksi AI selesai.');
        } catch (\Throwable $e) {
            return back()->with('error', 'AI gagal: '.$e->getMessage());
        }
    }

    /** Edit ulang feedback/skor yang sudah ada (meluruskan hasil AI). */
    public function editFeedback(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorizeSubmission($submission);
        $data = $request->validate([
            'score'    => 'required|integer|min:0|max:100',
            'feedback' => 'required|string|max:4000',
        ]);
        $submission->update([
            'score' => $data['score'], 'feedback' => $data['feedback'],
            'manually_graded' => true, 'graded_by' => Auth::id(), 'graded_at' => now(),
        ]);
        $this->recomputeAttemptTotal($submission->exam_attempt_id);
        return back()->with('success', 'Hasil koreksi diperbarui.');
    }

    protected function recomputeAttemptTotal(?int $attemptId): void
    {
        if (! $attemptId) return;
        $attempt = ExamAttempt::find($attemptId);
        if (! $attempt) return;
        $subs = $attempt->submissions;
        $graded = $subs->whereNotNull('score');
        if ($graded->count() === 0) return;
        $attempt->update([
            'total_score' => (int) round($graded->avg('score')),
            'max_score'   => 100,
        ]);
    }

    protected function authorizeAttempt(ExamAttempt $a): void
    {
        abort_unless($a->exam && $a->exam->teacher_id === Auth::id(), 403);
    }

    protected function authorizeSubmission(Submission $s): void
    {
        $teacher = $s->question?->material?->teacher_id;
        abort_unless($teacher === Auth::id(), 403);
    }
}

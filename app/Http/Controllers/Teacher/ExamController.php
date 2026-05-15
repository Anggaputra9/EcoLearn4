<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function indexForMaterial(Material $material): View
    {
        $this->authorizeMaterial($material);
        $exams = $material->exams()->withCount('attempts')->latest()->get();
        return view('teacher.exams.index', compact('material', 'exams'));
    }

    public function store(Request $request, Material $material): RedirectResponse
    {
        $this->authorizeMaterial($material);
        $data = $this->validated($request);

        $data['material_id'] = $material->id;
        $data['teacher_id']  = Auth::id();
        // ikuti classroom dari material kalau ada
        $data['classroom_id'] = $material->classroom_id;

        Exam::create($data);
        return back()->with('success', 'Ujian dibuat sebagai draft.');
    }

    public function show(Exam $exam): View
    {
        $this->authorizeExam($exam);
        $exam->load(['attempts.user', 'material.questions']);

        // statistik ringkas
        $attempts = $exam->attempts;
        $stats = [
            'total'       => $attempts->count(),
            'submitted'   => $attempts->where('status', 'submitted')->count(),
            'in_progress' => $attempts->where('status', 'in_progress')->count(),
            'avg_score'   => round($attempts->whereNotNull('total_score')->avg('total_score') ?? 0, 1),
            'top'         => $attempts->whereNotNull('total_score')->sortByDesc('total_score')->take(10)->values(),
        ];

        return view('teacher.exams.show', compact('exam', 'stats'));
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);
        $data = $this->validated($request);
        $exam->update($data);
        return back()->with('success', 'Ujian diperbarui.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);
        $matId = $exam->material_id;
        $exam->delete();
        return redirect()->route('teacher.materials.exams', $matId)->with('success', 'Ujian dihapus.');
    }

    public function publish(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);
        if ($exam->material->questions()->count() === 0) {
            return back()->with('error', 'Tambahkan minimal satu soal sebelum mempublikasikan ujian.');
        }
        $exam->update(['status' => 'published']);
        return back()->with('success', 'Ujian dipublikasikan. Siswa kini bisa mulai mengerjakan.');
    }

    public function close(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);
        $exam->update(['status' => 'closed']);
        return back()->with('success', 'Ujian ditutup.');
    }

    /** Rilis hasil ke seluruh peserta (kirim email + tandai result_released). */
    public function releaseResults(Exam $exam, NotificationService $notif): RedirectResponse
    {
        $this->authorizeExam($exam);
        $count = 0;
        foreach ($exam->attempts()->where('status', 'submitted')->with('user')->get() as $att) {
            $att->update(['result_released' => true]);
            if ($att->user && $att->total_score !== null) {
                $notif->notifyExamGraded($att->user, $exam->title, (int) $att->total_score, route('teacher.exams.show', $exam));
            }
            $count++;
        }
        return back()->with('success', "Hasil dirilis & {$count} email notifikasi dikirim.");
    }

    public function downloadReport(Exam $exam): Response
    {
        $this->authorizeExam($exam);
        $exam->load(['attempts.user', 'attempts.submissions.question', 'material.questions']);

        $pdf = Pdf::loadView('teacher.exams.report', ['exam' => $exam])
            ->setPaper('a4', 'portrait');

        return $pdf->download('Laporan-'.str()->slug($exam->title).'-'.now()->format('Ymd').'.pdf');
    }

    /* ============================================================
     * Helpers
     * ============================================================ */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'title'                    => 'required|string|max:255',
            'description'              => 'nullable|string|max:2000',
            'duration_minutes'         => 'required|integer|min:0|max:600',
            'starts_at'                => 'nullable|date',
            'ends_at'                  => 'nullable|date|after_or_equal:starts_at',

            'prevent_tab_switch'       => 'sometimes|boolean',
            'max_tab_switch'           => 'required|integer|min:0|max:50',
            'prevent_copy_paste'       => 'sometimes|boolean',
            'prevent_right_click'      => 'sometimes|boolean',
            'fullscreen_required'      => 'sometimes|boolean',
            'shuffle_questions'        => 'sometimes|boolean',

            'grading_mode'             => 'required|in:auto_ai,manual,hybrid',

            'show_result_after_submit' => 'sometimes|boolean',
            'show_leaderboard'         => 'sometimes|boolean',
            'allow_review_answer'      => 'sometimes|boolean',
        ]) + [
            'prevent_tab_switch'       => $request->boolean('prevent_tab_switch'),
            'prevent_copy_paste'       => $request->boolean('prevent_copy_paste'),
            'prevent_right_click'      => $request->boolean('prevent_right_click'),
            'fullscreen_required'      => $request->boolean('fullscreen_required'),
            'shuffle_questions'        => $request->boolean('shuffle_questions'),
            'show_result_after_submit' => $request->boolean('show_result_after_submit'),
            'show_leaderboard'         => $request->boolean('show_leaderboard'),
            'allow_review_answer'      => $request->boolean('allow_review_answer'),
        ];
    }

    protected function authorizeMaterial(Material $m): void
    {
        abort_unless($m->teacher_id === Auth::id(), 403);
    }

    protected function authorizeExam(Exam $e): void
    {
        abort_unless($e->teacher_id === Auth::id(), 403);
    }
}

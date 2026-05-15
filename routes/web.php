<?php

use App\Http\Controllers\Admin\AiKeyController;
use App\Http\Controllers\Admin\AppController as AdminAppController;
use App\Http\Controllers\Admin\ChangelogController;
use App\Http\Controllers\Admin\MailController;
use App\Http\Controllers\Admin\MailKeyController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\ClassroomController as StudentClassroomController;
use App\Http\Controllers\Student\ExamRunController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Teacher\ClassroomController as TeacherClassroomController;
use App\Http\Controllers\Teacher\ExamController;
use App\Http\Controllers\Teacher\SubmissionController;
use App\Http\Controllers\TeacherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('landing');
});

/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo/delete', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');

    // Diskusi (semua role yang punya akses materi)
    Route::post('/discussions/{material}', [DiscussionController::class, 'store'])->name('discussions.store');
    Route::delete('/discussions/{discussion}', [DiscussionController::class, 'destroy'])->name('discussions.destroy');
    Route::post('/discussions/{discussion}/resolve', [DiscussionController::class, 'resolve'])->name('discussions.resolve');

    /* ===== Admin ===== */
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/users',          [AdminController::class, 'users'])->name('admin.users');
        Route::post('/users',         [AdminController::class, 'storeUser']);
        Route::put('/users/{id}',     [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}',  [AdminController::class, 'destroyUser']);

        Route::get('/menus',          [AdminController::class, 'menus'])->name('admin.menus');
        Route::post('/menus',         [AdminController::class, 'storeMenu']);
        Route::put('/menus/{id}',     [AdminController::class, 'updateMenu']);
        Route::delete('/menus/{id}',  [AdminController::class, 'destroyMenu']);

        Route::get('/app',  [AdminAppController::class, 'edit'])->name('admin.app');
        Route::put('/app',  [AdminAppController::class, 'update']);

        Route::get('/settings',       [SettingController::class, 'edit'])->name('admin.settings');
        Route::put('/settings',       [SettingController::class, 'update']);
        Route::post('/settings/test', [SettingController::class, 'test'])->name('admin.settings.test');

        // AI Key Pool
        Route::get('/ai-keys',                       [AiKeyController::class, 'index'])->name('admin.aiKeys');
        Route::post('/ai-keys',                      [AiKeyController::class, 'store']);
        Route::put('/ai-keys/{aiKey}',               [AiKeyController::class, 'update']);
        Route::delete('/ai-keys/{aiKey}',            [AiKeyController::class, 'destroy']);
        Route::post('/ai-keys/reorder',              [AiKeyController::class, 'reorder']);
        Route::post('/ai-keys/{aiKey}/test',         [AiKeyController::class, 'test']);
        Route::post('/ai-keys/{aiKey}/reset-quota',  [AiKeyController::class, 'resetQuota']);

        // Mail (provider default + tes kirim)
        Route::get('/mail',       [MailController::class, 'edit'])->name('admin.mail');
        Route::put('/mail',       [MailController::class, 'update']);
        Route::post('/mail/test', [MailController::class, 'test']);

        // Mail Key Pool
        Route::get('/mail-keys',                       [MailKeyController::class, 'index'])->name('admin.mailKeys');
        Route::post('/mail-keys',                      [MailKeyController::class, 'store']);
        Route::put('/mail-keys/{mailKey}',             [MailKeyController::class, 'update']);
        Route::delete('/mail-keys/{mailKey}',          [MailKeyController::class, 'destroy']);
        Route::post('/mail-keys/reorder',              [MailKeyController::class, 'reorder']);
        Route::post('/mail-keys/{mailKey}/reset-quota',[MailKeyController::class, 'resetQuota']);

        Route::get('/changelogs',                  [ChangelogController::class, 'index'])->name('admin.changelogs');
        Route::post('/changelogs',                 [ChangelogController::class, 'store']);
        Route::put('/changelogs/{changelog}',      [ChangelogController::class, 'update']);
        Route::delete('/changelogs/{changelog}',   [ChangelogController::class, 'destroy']);
    });

    /* ===== Guru ===== */
    Route::prefix('teacher')->name('teacher.')->middleware('role:teacher')->group(function () {
        Route::get('/',                                 [TeacherController::class, 'index'])->name('index');

        // Materi
        Route::post('/materials/generate-ajax',         [TeacherController::class, 'generateMaterialAjax'])->name('materials.generate.ajax');
        Route::post('/materials',                       [TeacherController::class, 'storeMaterial'])->name('materials.store');
        Route::get('/materials/{material}',             [TeacherController::class, 'showMaterial'])->name('materials.show');
        Route::get('/materials/{material}/edit',        [TeacherController::class, 'editMaterial'])->name('materials.edit');
        Route::put('/materials/{material}',             [TeacherController::class, 'updateMaterial'])->name('materials.update');
        Route::delete('/materials/{material}',          [TeacherController::class, 'destroyMaterial'])->name('materials.destroy');

        // Soal
        Route::post('/materials/{material}/questions',          [TeacherController::class, 'generateQuestions'])->name('questions.generate');
        Route::post('/materials/{material}/questions/manual',   [TeacherController::class, 'storeQuestion'])->name('questions.store');
        Route::put('/questions/{question}',                     [TeacherController::class, 'updateQuestion'])->name('questions.update');
        Route::delete('/questions/{question}',                  [TeacherController::class, 'destroyQuestion'])->name('questions.destroy');

        Route::get('/materials/{material}/submissions', [TeacherController::class, 'submissions'])->name('submissions');

        // Kelas
        Route::get('/classrooms',                              [TeacherClassroomController::class, 'index'])->name('classrooms.index');
        Route::post('/classrooms',                             [TeacherClassroomController::class, 'store'])->name('classrooms.store');
        Route::get('/classrooms/{classroom}',                  [TeacherClassroomController::class, 'show'])->name('classrooms.show');
        Route::put('/classrooms/{classroom}',                  [TeacherClassroomController::class, 'update'])->name('classrooms.update');
        Route::delete('/classrooms/{classroom}',               [TeacherClassroomController::class, 'destroy'])->name('classrooms.destroy');
        Route::post('/classrooms/{classroom}/regenerate',      [TeacherClassroomController::class, 'regenerateCode'])->name('classrooms.regen');
        Route::delete('/classrooms/{classroom}/members/{userId}', [TeacherClassroomController::class, 'removeMember'])->name('classrooms.removeMember');

        // Ujian (per materi)
        Route::post('/materials/{material}/exams', [ExamController::class, 'store'])->name('exams.store');
        Route::get('/exams/{exam}',                [ExamController::class, 'show'])->name('exams.show');
        Route::put('/exams/{exam}',                [ExamController::class, 'update'])->name('exams.update');
        Route::delete('/exams/{exam}',             [ExamController::class, 'destroy'])->name('exams.destroy');
        Route::post('/exams/{exam}/publish',       [ExamController::class, 'publish'])->name('exams.publish');
        Route::post('/exams/{exam}/close',         [ExamController::class, 'close'])->name('exams.close');
        Route::post('/exams/{exam}/release',       [ExamController::class, 'releaseResults'])->name('exams.release');
        Route::get('/exams/{exam}/report',         [ExamController::class, 'downloadReport'])->name('exams.report');

        // Koreksi manual
        Route::get('/attempts/{attempt}/review',        [SubmissionController::class, 'reviewAttempt'])->name('attempts.review');
        Route::put('/submissions/{submission}/grade',   [SubmissionController::class, 'manualGrade'])->name('submissions.grade');
        Route::post('/submissions/{submission}/ai-grade', [SubmissionController::class, 'aiGrade'])->name('submissions.aiGrade');
        Route::put('/submissions/{submission}/feedback',  [SubmissionController::class, 'editFeedback'])->name('submissions.editFeedback');
    });

    /* ===== Siswa ===== */
    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('/',                              [StudentController::class, 'index'])->name('index');
        Route::get('/materials/{material}',          [StudentController::class, 'showMaterial'])->name('materials.show');
        Route::get('/questions/{question}/answer',   [StudentController::class, 'answerForm'])->name('questions.answer');
        Route::post('/questions/{question}/answer',  [StudentController::class, 'submitAnswer'])->name('questions.submit');
        Route::get('/submissions/{submission}',      [StudentController::class, 'showSubmission'])->name('submissions.show');

        // Kelas
        Route::get('/classrooms',                       [StudentClassroomController::class, 'index'])->name('classrooms.index');
        Route::post('/classrooms/join',                 [StudentClassroomController::class, 'join'])->name('classrooms.join');
        Route::get('/classrooms/{classroom}',           [StudentClassroomController::class, 'show'])->name('classrooms.show');
        Route::delete('/classrooms/{classroom}/leave',  [StudentClassroomController::class, 'leave'])->name('classrooms.leave');

        // Ujian
        Route::get('/exams/{exam}',                          [ExamRunController::class, 'lobby'])->name('exams.lobby');
        Route::post('/exams/{exam}/start',                   [ExamRunController::class, 'start'])->name('exams.start');
        Route::get('/exams/{exam}/run',                      [ExamRunController::class, 'run'])->name('exams.run');
        Route::post('/exams/{exam}/save/{question}',         [ExamRunController::class, 'save'])->name('exams.save');
        Route::post('/exams/{exam}/cheat',                   [ExamRunController::class, 'reportCheat'])->name('exams.cheat');
        Route::post('/exams/{exam}/submit',                  [ExamRunController::class, 'submit'])->name('exams.submit');
        Route::get('/exams/{exam}/result',                   [ExamRunController::class, 'result'])->name('exams.result');
    });
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Catch-all menu dinamis (PALING BAWAH)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/{slug}', function (Request $request) {
        $url = '/'.ltrim($request->path(), '/');
        $menu = DB::table('menus')
            ->where('url', $url)
            ->where('role_id', Auth::user()->role_id)
            ->first();
        if ($menu) {
            return view('dynamic-page', ['menu' => $menu]);
        }
        abort(404);
    })->where('slug', '.*');
});

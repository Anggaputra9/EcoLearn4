<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $classrooms = $user->classroomsJoined()->with(['teacher', 'materials'])->paginate(12);
        return view('student.classrooms.index', compact('classrooms'));
    }

    public function join(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string|max:12']);
        $code = strtoupper(trim($data['code']));

        $classroom = Classroom::where('code', $code)->where('is_active', true)->first();
        if (! $classroom) return back()->with('error', 'Kode kelas tidak ditemukan atau sudah tidak aktif.');

        $classroom->members()->syncWithoutDetaching([Auth::id() => ['joined_at' => now()]]);
        return redirect()->route('student.classrooms.show', $classroom)->with('success', 'Berhasil bergabung ke kelas.');
    }

    public function show(Classroom $classroom): View
    {
        $this->authorizeMember($classroom);
        $classroom->load(['teacher', 'materials.questions', 'materials.exams']);
        return view('student.classrooms.show', compact('classroom'));
    }

    public function leave(Classroom $classroom): RedirectResponse
    {
        $classroom->members()->detach(Auth::id());
        return redirect()->route('student.classrooms.index')->with('success', 'Anda keluar dari kelas.');
    }

    protected function authorizeMember(Classroom $c): void
    {
        abort_unless($c->members()->whereKey(Auth::id())->exists(), 403, 'Anda bukan anggota kelas ini.');
    }
}

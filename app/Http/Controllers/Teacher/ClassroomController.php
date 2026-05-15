<?php

namespace App\Http\Controllers\Teacher;

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
        $classrooms = Classroom::with(['members', 'materials'])
            ->where('teacher_id', Auth::id())
            ->latest()
            ->paginate(12);

        return view('teacher.classrooms.index', compact('classrooms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'subject'     => 'nullable|string|max:150',
            'description' => 'nullable|string|max:2000',
        ]);
        $data['teacher_id'] = Auth::id();
        $data['code'] = Classroom::generateUniqueCode();

        Classroom::create($data);
        return back()->with('success', 'Kelas berhasil dibuat. Kode: '.$data['code']);
    }

    public function show(Classroom $classroom): View
    {
        $this->authorizeOwn($classroom);
        $classroom->load(['members' => fn ($q) => $q->orderBy('name'), 'materials.questions']);
        return view('teacher.classrooms.show', compact('classroom'));
    }

    public function update(Request $request, Classroom $classroom): RedirectResponse
    {
        $this->authorizeOwn($classroom);
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'subject'     => 'nullable|string|max:150',
            'description' => 'nullable|string|max:2000',
            'is_active'   => 'sometimes|boolean',
        ]);
        $data['is_active'] = (bool) $request->boolean('is_active', true);
        $classroom->update($data);
        return back()->with('success', 'Kelas diperbarui.');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        $this->authorizeOwn($classroom);
        $classroom->delete();
        return redirect()->route('teacher.classrooms.index')->with('success', 'Kelas dihapus.');
    }

    public function regenerateCode(Classroom $classroom): RedirectResponse
    {
        $this->authorizeOwn($classroom);
        $classroom->update(['code' => Classroom::generateUniqueCode()]);
        return back()->with('success', 'Kode kelas baru: '.$classroom->code);
    }

    public function removeMember(Classroom $classroom, int $userId): RedirectResponse
    {
        $this->authorizeOwn($classroom);
        $classroom->members()->detach($userId);
        return back()->with('success', 'Siswa dikeluarkan dari kelas.');
    }

    protected function authorizeOwn(Classroom $c): void
    {
        abort_unless($c->teacher_id === Auth::id(), 403);
    }
}

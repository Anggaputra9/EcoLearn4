<?php

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\Material;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscussionController extends Controller
{
    /**
     * Buat thread baru atau balasan terhadap thread.
     * Dipakai oleh siswa & guru di halaman materi.
     */
    public function store(Request $request, Material $material, NotificationService $notif): RedirectResponse
    {
        $data = $request->validate([
            'body'      => 'required|string|max:4000',
            'parent_id' => 'nullable|integer|exists:discussions,id',
        ]);

        // Authorization: siswa harus boleh akses materi (member kelas atau materi global)
        $this->ensureCanAccess($material);

        $discussion = Discussion::create([
            'material_id' => $material->id,
            'classroom_id' => $material->classroom_id,
            'parent_id'   => $data['parent_id'] ?? null,
            'user_id'     => Auth::id(),
            'body'        => $data['body'],
        ]);

        // Email notifikasi
        if ($discussion->parent_id) {
            // Siswa membalas thread, atau guru menjawab → kirim ke pemilik thread
            $notif->notifyStudentReply($discussion);
        } else {
            // Pertanyaan baru dari siswa → kirim ke guru pemilik materi
            if (Auth::id() !== $material->teacher_id) {
                $notif->notifyTeacherNewQuestion($discussion);
            }
        }

        return back()->with('success', $discussion->parent_id ? 'Balasan terkirim.' : 'Pertanyaan terkirim.');
    }

    public function destroy(Discussion $discussion): RedirectResponse
    {
        // Pemilik post atau guru pemilik materi boleh hapus
        $material = $discussion->material;
        $isOwner = $discussion->user_id === Auth::id();
        $isTeacher = $material && $material->teacher_id === Auth::id();
        abort_unless($isOwner || $isTeacher, 403);

        $discussion->delete();
        return back()->with('success', 'Diskusi dihapus.');
    }

    public function resolve(Discussion $discussion): RedirectResponse
    {
        $material = $discussion->material;
        abort_unless($material && $material->teacher_id === Auth::id(), 403);
        $discussion->update(['is_resolved' => ! $discussion->is_resolved]);
        return back();
    }

    protected function ensureCanAccess(Material $material): void
    {
        $user = Auth::user();
        if ($user->isAdmin() || $user->id === $material->teacher_id) return;
        if (! $material->is_published) abort(403);

        if ($material->classroom_id) {
            $isMember = $material->classroom?->members()->whereKey($user->id)->exists();
            abort_unless($isMember, 403, 'Materi ini hanya untuk anggota kelas.');
        }
    }
}

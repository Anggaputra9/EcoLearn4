<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Changelog;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $kind = $request->get('kind');

        $changelogs = Changelog::query()
            ->when($q, fn ($qq) => $qq->where(function ($w) use ($q) {
                $w->where('version', 'like', "%$q%")
                  ->orWhere('title', 'like', "%$q%")
                  ->orWhere('notes', 'like', "%$q%");
            }))
            ->when($kind, fn ($qq) => $qq->where('kind', $kind))
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.changelogs', compact('changelogs', 'q', 'kind'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'version'     => 'required|string|max:20',
            'title'       => 'required|string|max:255',
            'released_at' => 'required|date',
            'notes'       => 'required|string',
            'kind'        => 'required|in:major,minor,patch,hotfix',
        ]);
        Changelog::create($data);
        return back()->with('success', 'Changelog v'.$data['version'].' berhasil ditambahkan.');
    }

    public function update(Request $request, Changelog $changelog)
    {
        $data = $request->validate([
            'version'     => 'required|string|max:20',
            'title'       => 'required|string|max:255',
            'released_at' => 'required|date',
            'notes'       => 'required|string',
            'kind'        => 'required|in:major,minor,patch,hotfix',
        ]);
        $changelog->update($data);
        return back()->with('success', 'Changelog diperbarui.');
    }

    public function destroy(Changelog $changelog)
    {
        $changelog->delete();
        return back()->with('success', 'Changelog dihapus.');
    }
}

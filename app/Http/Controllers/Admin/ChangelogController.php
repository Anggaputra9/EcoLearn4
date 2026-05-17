<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ChangelogService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * Read-only changelog: data dibaca langsung dari CHANGELOG.md
 * di root proyek. Tidak ada operasi tulis ke database.
 */
class ChangelogController extends Controller
{
    public function index(Request $request, ChangelogService $service)
    {
        $q    = trim((string) $request->get('q', ''));
        $kind = $request->get('kind');

        $items   = $service->search($q, $kind);
        $perPage = 10;
        $page    = (int) ($request->get('page', 1) ?: 1);

        $changelogs = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path'  => Paginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        return view('admin.changelogs', compact('changelogs', 'q', 'kind'));
    }
}

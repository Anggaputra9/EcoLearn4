<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailKey;
use App\Services\MailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MailKeyController extends Controller
{
    public function index(MailService $mail): View
    {
        $keys = MailKey::orderBy('provider')->orderBy('priority')->orderBy('id')->get();
        return view('admin.mail-keys', [
            'keys'      => $keys,
            'providers' => collect($mail->providers())->except('smtp')->all(),
        ]);
    }

    public function store(Request $request, MailService $mail): RedirectResponse
    {
        $providers = collect($mail->providers())->except('smtp')->keys()->all();
        $data = $request->validate([
            'label'              => 'required|string|max:120',
            'provider'           => 'required|in:'.implode(',', $providers),
            'api_key'            => 'required|string|max:512',
            'api_secret'         => 'nullable|string|max:512',
            'priority'           => 'required|integer|min:0|max:9999',
            'is_active'          => 'sometimes|boolean',
            'quota_limit'        => 'nullable|integer|min:0',
            'quota_reset_period' => 'required|in:none,daily,monthly',
        ]);
        $data['is_active'] = (bool) $request->boolean('is_active', true);
        $data['quota_used'] = 0;
        $data['quota_reset_at'] = match ($data['quota_reset_period']) {
            'daily'   => now()->addDay()->startOfDay(),
            'monthly' => now()->addMonth()->startOfMonth(),
            default   => null,
        };
        MailKey::create($data);
        return back()->with('success', 'Mail key ditambahkan.');
    }

    public function update(Request $request, MailKey $mailKey, MailService $mail): RedirectResponse
    {
        $providers = collect($mail->providers())->except('smtp')->keys()->all();
        $data = $request->validate([
            'label'              => 'required|string|max:120',
            'provider'           => 'required|in:'.implode(',', $providers),
            'api_key'            => 'nullable|string|max:512',
            'api_secret'         => 'nullable|string|max:512',
            'priority'           => 'required|integer|min:0|max:9999',
            'is_active'          => 'sometimes|boolean',
            'quota_limit'        => 'nullable|integer|min:0',
            'quota_reset_period' => 'required|in:none,daily,monthly',
        ]);
        $data['is_active'] = (bool) $request->boolean('is_active', true);
        if (empty($data['api_key']))    unset($data['api_key']);
        if (empty($data['api_secret'])) unset($data['api_secret']);
        $mailKey->update($data);
        return back()->with('success', 'Mail key diperbarui.');
    }

    public function destroy(MailKey $mailKey): RedirectResponse
    {
        $mailKey->delete();
        return back()->with('success', 'Mail key dihapus.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:mail_keys,id',
        ]);
        foreach ($data['order'] as $idx => $id) {
            MailKey::whereKey($id)->update(['priority' => $idx]);
        }
        return back()->with('success', 'Urutan disimpan.');
    }

    public function resetQuota(MailKey $mailKey): RedirectResponse
    {
        $mailKey->update(['quota_used' => 0, 'last_error' => null]);
        return back()->with('success', 'Kuota direset.');
    }
}

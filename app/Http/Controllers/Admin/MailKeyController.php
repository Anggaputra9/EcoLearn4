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
            'label'      => 'required|string|max:120',
            'provider'   => 'required|in:'.implode(',', $providers),
            'api_key'    => 'required|string|max:512',
            'api_secret' => 'nullable|string|max:512',
            'priority'   => 'nullable|integer|min:0|max:9999',
            'is_active'  => 'sometimes|boolean',
        ]);

        [$defLimit, $defPeriod] = MailKey::defaultQuotaFor($data['provider']);
        $data['quota_limit']        = $defLimit;
        $data['quota_reset_period'] = $defPeriod;
        $data['quota_used']         = 0;
        $data['quota_reset_at']     = $this->nextResetAt($defPeriod);

        $data['priority'] = $request->filled('priority')
            ? (int) $data['priority']
            : (int) (MailKey::where('provider', $data['provider'])->max('priority') ?? -1) + 1;

        $data['is_active'] = (bool) $request->boolean('is_active', true);

        MailKey::create($data);
        return back()->with('success', 'Mail key ditambahkan dengan kuota otomatis ('.number_format($defLimit).' / '.$defPeriod.').');
    }

    public function update(Request $request, MailKey $mailKey, MailService $mail): RedirectResponse
    {
        $providers = collect($mail->providers())->except('smtp')->keys()->all();
        $data = $request->validate([
            'label'      => 'required|string|max:120',
            'provider'   => 'required|in:'.implode(',', $providers),
            'api_key'    => 'nullable|string|max:512',
            'api_secret' => 'nullable|string|max:512',
            'priority'   => 'nullable|integer|min:0|max:9999',
            'is_active'  => 'sometimes|boolean',
        ]);

        if ($mailKey->provider !== $data['provider']) {
            [$defLimit, $defPeriod] = MailKey::defaultQuotaFor($data['provider']);
            $data['quota_limit']        = $defLimit;
            $data['quota_reset_period'] = $defPeriod;
            $data['quota_reset_at']     = $this->nextResetAt($defPeriod);
        }

        $data['is_active'] = (bool) $request->boolean('is_active', true);
        $data['priority']  = $request->filled('priority') ? (int) $data['priority'] : $mailKey->priority;

        if (empty($data['api_key']))    unset($data['api_key']);
        if (empty($data['api_secret'])) unset($data['api_secret']);

        $mailKey->update($data);
        return back()->with('success', 'Mail key diperbarui.');
    }

    private function nextResetAt(string $period): ?\Illuminate\Support\Carbon
    {
        return match ($period) {
            'daily'   => now()->addDay()->startOfDay(),
            'monthly' => now()->addMonth()->startOfMonth(),
            default   => null,
        };
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

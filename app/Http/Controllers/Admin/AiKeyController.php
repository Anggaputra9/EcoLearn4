<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiKey;
use App\Services\AIService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiKeyController extends Controller
{
    public function index(AIService $ai): View
    {
        $keys = AiKey::orderBy('provider')->orderBy('priority')->orderBy('id')->get();

        return view('admin.ai-keys', [
            'keys'      => $keys,
            'providers' => $ai->providers(),
            'aiService' => $ai,
        ]);
    }

    public function store(Request $request, AIService $ai): RedirectResponse
    {
        $data = $request->validate([
            'label'    => 'required|string|max:120',
            'provider' => 'required|in:'.implode(',', array_keys($ai->providers())),
            'model'    => 'nullable|string|max:120',
            'api_key'  => 'required|string|max:512',
            'priority' => 'nullable|integer|min:0|max:9999',
            'is_active'=> 'sometimes|boolean',
        ]);

        // Auto: kuota & periode reset diisi otomatis sesuai default tier free.
        [$defLimit, $defPeriod] = AiKey::defaultQuotaFor($data['provider']);
        $data['quota_limit']        = $defLimit;
        $data['quota_reset_period'] = $defPeriod;
        $data['quota_used']         = 0;
        $data['quota_reset_at']     = $this->nextResetAt($defPeriod);

        // Auto: prioritas = posisi terakhir untuk provider ini.
        $data['priority'] = $request->filled('priority')
            ? (int) $data['priority']
            : (int) (AiKey::where('provider', $data['provider'])->max('priority') ?? -1) + 1;

        $data['is_active'] = (bool) $request->boolean('is_active', true);

        AiKey::create($data);
        return back()->with('success', 'API key ditambahkan dengan kuota otomatis ('.number_format($defLimit).' / '.$defPeriod.').');
    }

    public function update(Request $request, AiKey $aiKey, AIService $ai): RedirectResponse
    {
        $data = $request->validate([
            'label'    => 'required|string|max:120',
            'provider' => 'required|in:'.implode(',', array_keys($ai->providers())),
            'model'    => 'nullable|string|max:120',
            'api_key'  => 'nullable|string|max:512',     // kosong = tetap
            'priority' => 'nullable|integer|min:0|max:9999',
            'is_active'=> 'sometimes|boolean',
        ]);

        // Jika provider berubah, sesuaikan ulang kuota otomatis.
        if ($aiKey->provider !== $data['provider']) {
            [$defLimit, $defPeriod] = AiKey::defaultQuotaFor($data['provider']);
            $data['quota_limit']        = $defLimit;
            $data['quota_reset_period'] = $defPeriod;
            $data['quota_reset_at']     = $this->nextResetAt($defPeriod);
        }

        $data['is_active'] = (bool) $request->boolean('is_active', true);
        $data['priority']  = $request->filled('priority') ? (int) $data['priority'] : $aiKey->priority;

        if (empty($data['api_key'])) unset($data['api_key']);

        $aiKey->update($data);
        return back()->with('success', 'API key diperbarui.');
    }

    private function nextResetAt(string $period): ?\Illuminate\Support\Carbon
    {
        return match ($period) {
            'daily'   => now()->addDay()->startOfDay(),
            'monthly' => now()->addMonth()->startOfMonth(),
            default   => null,
        };
    }


    public function destroy(AiKey $aiKey): RedirectResponse
    {
        $aiKey->delete();
        return back()->with('success', 'API key dihapus.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:ai_keys,id',
        ]);
        foreach ($data['order'] as $idx => $id) {
            AiKey::whereKey($id)->update(['priority' => $idx]);
        }
        return back()->with('success', 'Urutan API key disimpan.');
    }

    public function resetQuota(AiKey $aiKey): RedirectResponse
    {
        $aiKey->update(['quota_used' => 0, 'last_error' => null]);
        return back()->with('success', 'Kuota direset.');
    }

    public function test(AiKey $aiKey): RedirectResponse
    {
        try {
            // Pakai service tetapi paksa pakai key ini
            $svc = new AIService();
            // memanggil generateText akan memutar semua key, jadi kita panggil langsung
            $reflection = new \ReflectionMethod($svc, match ($aiKey->provider) {
                'gemini' => 'callGemini',
                'anthropic' => 'callAnthropic',
                default => 'callOpenAICompatible',
            });
            $reflection->setAccessible(true);

            $args = $aiKey->provider === 'gemini'
                ? [$aiKey->api_key, $aiKey->model ?: $svc->defaultModel('gemini'), 'Balas: pong', null, false]
                : ($aiKey->provider === 'anthropic'
                    ? [$aiKey->api_key, $aiKey->model ?: $svc->defaultModel('anthropic'), 'Balas: pong', null, false]
                    : [$aiKey->provider, $aiKey->api_key, $aiKey->model ?: $svc->defaultModel($aiKey->provider), 'Balas: pong', null, false]);

            $reply = $reflection->invokeArgs($svc, $args);

            $aiKey->update(['last_error' => null, 'last_used_at' => now()]);
            return back()->with('success', "Tes berhasil. Balasan: ".mb_substr(trim($reply), 0, 120));
        } catch (\Throwable $e) {
            $aiKey->update(['last_error' => mb_substr($e->getMessage(), 0, 500)]);
            return back()->with('error', 'Tes gagal: '.$e->getMessage());
        }
    }
}

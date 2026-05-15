<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiKey;
use App\Models\Setting;
use App\Services\AIService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Halaman gabungan: Konfigurasi AI Default + API Key Pool dalam satu view (admin/ai.blade.php).
     */
    public function hub(AIService $ai): View
    {
        $provider = $ai->defaultProvider();
        $models   = $ai->listModels($provider);
        if (empty($models)) $models = $ai->staticModelList($provider);

        $keys = AiKey::orderBy('provider')->orderBy('priority')->orderBy('id')->get();

        return view('admin.ai', [
            'providers'   => $ai->providers(),
            'provider'    => $provider,
            'model'       => $ai->defaultModel($provider),
            'models'      => $models,
            'staticLists' => collect($ai->providers())->mapWithKeys(fn ($n, $p) => [$p => $ai->staticModelList($p)])->all(),
            'aiService'   => $ai,
            'keys'        => $keys,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => 'required|string|max:40',
            'model'    => 'required|string|max:120',
        ]);
        Setting::put('ai.default_provider', $data['provider'], 'ai');
        Setting::put("ai.default_model.{$data['provider']}", $data['model'], 'ai');
        // Backward compat
        if ($data['provider'] === 'gemini') {
            Setting::put('gemini.model', $data['model'], 'gemini');
        }
        return redirect('/admin/ai?tab=general')->with('success', 'Pengaturan AI default disimpan.');
    }

    public function test(AIService $ai): RedirectResponse
    {
        try {
            $reply = $ai->generateText('Balas dengan satu kalimat: "Koneksi AI sukses."');
            $used  = $ai->lastUsedKey()?->label;
            return back()->with('success', 'Tes AI berhasil'.($used ? " (key: {$used})" : '').'. Balasan: '.$reply);
        } catch (\Throwable $e) {
            return back()->with('error', 'Tes AI gagal: '.$e->getMessage());
        }
    }
}

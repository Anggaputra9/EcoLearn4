<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AIService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(AIService $ai): View
    {
        $provider = $ai->defaultProvider();
        $models = $ai->listModels($provider);
        if (empty($models)) $models = $ai->staticModelList($provider);

        return view('admin.settings', [
            'providers'   => $ai->providers(),
            'provider'    => $provider,
            'model'       => $ai->defaultModel($provider),
            'models'      => $models,
            'staticLists' => collect($ai->providers())->mapWithKeys(fn ($n, $p) => [$p => $ai->staticModelList($p)])->all(),
            'aiService'   => $ai,
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
        return back()->with('success', 'Pengaturan AI default disimpan.');
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

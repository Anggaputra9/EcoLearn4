<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiKey;
use App\Models\Setting;
use App\Services\AIService;
use App\Services\ScrapedImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'providers'        => $ai->providers(),
            'customProviders'  => $ai->customProviderConfigs(),
            'provider'         => $provider,
            'model'            => $ai->defaultModel($provider),
            'models'           => $models,
            'staticLists'      => collect($ai->providers())->mapWithKeys(fn ($n, $p) => [$p => $ai->staticModelList($p)])->all(),
            'aiService'        => $ai,
            'keys'             => $keys,
        ]);
    }

    public function update(Request $request, AIService $ai): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:40', Rule::in($ai->providerKeys())],
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

    public function testScrapedImages(ScrapedImageService $scraper): RedirectResponse
    {
        $result = $scraper->test('earth environment ecology nature');
        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    public function storeCustomProvider(Request $request, AIService $ai): RedirectResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:80',
            'slug'       => ['nullable', 'string', 'max:40', 'regex:/^custom_[a-z0-9_-]+$/', Rule::notIn(array_keys($ai->customProviderConfigs()))],
            'base_url'   => 'required|url|max:255',
            'chat_url'   => 'nullable|url|max:255',
            'models_url' => 'nullable|url|max:255',
        ]);

        $slug = $data['slug'] ?? AIService::makeCustomSlug($data['name']);
        if (isset($ai->providers()[$slug]) && ! $ai->isCustomProvider($slug)) {
            return back()->with('error', 'Slug bentrok dengan provider bawaan. Gunakan slug lain.');
        }

        $ai->saveCustomProvider($slug, $data);

        return redirect('/admin/ai?tab=custom')->with('success', "Provider kustom [{$data['name']}] ditambahkan. Tambahkan API key lalu muat daftar model.");
    }

    public function updateCustomProvider(Request $request, string $slug, AIService $ai): RedirectResponse
    {
        if (! $ai->getCustomProviderConfig($slug)) {
            return back()->with('error', 'Provider kustom tidak ditemukan.');
        }

        $data = $request->validate([
            'name'       => 'required|string|max:80',
            'base_url'   => 'required|url|max:255',
            'chat_url'   => 'nullable|url|max:255',
            'models_url' => 'nullable|url|max:255',
        ]);

        $ai->saveCustomProvider($slug, $data);

        return redirect('/admin/ai?tab=custom')->with('success', 'Provider kustom diperbarui.');
    }

    public function destroyCustomProvider(string $slug, AIService $ai): RedirectResponse
    {
        if (! $ai->getCustomProviderConfig($slug)) {
            return back()->with('error', 'Provider kustom tidak ditemukan.');
        }

        if (AiKey::where('provider', $slug)->exists()) {
            return back()->with('error', 'Hapus atau pindahkan API key provider ini terlebih dahulu.');
        }

        if ($ai->defaultProvider() === $slug) {
            Setting::put('ai.default_provider', 'gemini', 'ai');
        }

        $ai->deleteCustomProvider($slug);

        return redirect('/admin/ai?tab=custom')->with('success', 'Provider kustom dihapus.');
    }

    /** AJAX: uji fetch model untuk provider kustom (sebelum/sesudah disimpan). */
    public function previewCustomModels(Request $request, AIService $ai): JsonResponse
    {
        $data = $request->validate([
            'provider'   => 'nullable|string|max:40',
            'base_url'   => 'nullable|url|max:255',
            'models_url' => 'nullable|url|max:255',
            'api_key'    => 'nullable|string|max:512',
        ]);

        $provider = (string) ($data['provider'] ?? '');
        $apiKey = (string) ($data['api_key'] ?? '');

        if ($provider !== '' && $ai->isCustomProvider($provider)) {
            if ($apiKey === '') {
                $apiKey = (string) (AiKey::where('provider', $provider)->orderBy('priority')->value('api_key') ?? '');
            }
            $urls = $ai->customProviderUrls($provider);
        } else {
            $base = rtrim((string) ($data['base_url'] ?? ''), '/');
            if ($base === '') {
                return response()->json(['ok' => false, 'message' => 'Base URL wajib diisi.'], 422);
            }
            if (! str_ends_with($base, '/v1')) {
                $base .= '/v1';
            }
            $urls = [
                'models' => (string) ($data['models_url'] ?? $base.'/models'),
            ];
        }

        if ($apiKey === '') {
            return response()->json(['ok' => false, 'message' => 'API key diperlukan untuk fetch model.'], 422);
        }

        $res = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->timeout(15)
            ->acceptJson()
            ->get($urls['models']);

        if (! $res->successful()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Gagal fetch model: HTTP '.$res->status(),
            ], 422);
        }

        $models = collect($res->json('data', []))
            ->map(fn ($m) => (string) ($m['id'] ?? ''))
            ->filter()
            ->values()
            ->all();

        return response()->json([
            'ok'     => true,
            'models' => $models,
            'count'  => count($models),
        ]);
    }
}

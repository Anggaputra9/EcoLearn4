<?php

namespace App\Services;

use App\Models\AiKey;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fetch gambar dari Google via Custom Search JSON API (searchType=image).
 *
 * Butuh API Key + Search Engine ID (CX) dengan "Image search" aktif.
 * @see https://developers.google.com/custom-search/v1/overview
 */
class GoogleImageService
{
    protected string $cacheDir;

    public function __construct()
    {
        $this->cacheDir = storage_path('app/material-images/google');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '' && $this->searchEngineId() !== '';
    }

    public function apiKey(): string
    {
        $fromSetting = trim((string) Setting::get('google.cse.api_key', ''));
        if ($fromSetting !== '') {
            return $fromSetting;
        }

        $fromEnv = trim((string) config('services.google_cse.api_key', ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        // Fallback: pakai Gemini key aktif (sering satu project Google Cloud).
        $gemini = AiKey::where('provider', 'gemini')
            ->where('is_active', true)
            ->orderBy('priority')
            ->value('api_key');

        return trim((string) ($gemini ?? Setting::get('gemini.api_key') ?? config('services.gemini.api_key', '')));
    }

    public function searchEngineId(): string
    {
        $fromSetting = trim((string) Setting::get('google.cse.cx', ''));

        return $fromSetting !== ''
            ? $fromSetting
            : trim((string) config('services.google_cse.cx', ''));
    }

    /**
     * Cari & unduh gambar Google. Kembalikan path file lokal (jpg) atau null.
     */
    public function fetch(string $query, string $cacheKey): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        if (! is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }

        $safeKey = Str::slug($cacheKey) ?: 'img';
        $path = $this->cacheDir.DIRECTORY_SEPARATOR.$safeKey.'.jpg';

        if (is_file($path) && filesize($path) > 2048) {
            return $path;
        }

        $query = trim(preg_replace('/\s+/', ' ', $query));
        if ($query === '') {
            return null;
        }

        try {
            $res = Http::timeout(12)->get('https://www.googleapis.com/customsearch/v1', [
                'key'          => $this->apiKey(),
                'cx'           => $this->searchEngineId(),
                'q'            => $query,
                'searchType'   => 'image',
                'num'          => 5,
                'safe'         => 'active',
                'imgSize'      => 'large',
                'imgType'      => 'photo',
                'rights'       => 'cc_publicdomain|cc_attribute|cc_sharealike|cc_noncommercial|cc_nonderived',
            ]);

            if (! $res->successful()) {
                Log::info('GoogleImageService search gagal', [
                    'status' => $res->status(),
                    'body'   => Str::limit($res->body(), 300),
                ]);

                return null;
            }

            foreach ((array) $res->json('items', []) as $item) {
                $url = (string) ($item['link'] ?? '');
                if ($url === '') {
                    continue;
                }

                $bytes = $this->downloadImage($url);
                if ($bytes !== null) {
                    file_put_contents($path, $bytes);

                    return $path;
                }
            }
        } catch (\Throwable $e) {
            Log::info('GoogleImageService error: '.$e->getMessage());
        }

        return null;
    }

    protected function downloadImage(string $url): ?string
    {
        try {
            $res = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'EcoLearn/1.0'])
                ->withOptions(['allow_redirects' => true])
                ->get($url);

            if (! $res->successful()) {
                return null;
            }

            $body = $res->body();
            if (strlen($body) < 2048) {
                return null;
            }

            $type = strtolower((string) $res->header('Content-Type'));
            if (! str_contains($type, 'image')) {
                return null;
            }

            return $body;
        } catch (\Throwable $e) {
            Log::info('GoogleImageService download gagal: '.$e->getMessage());

            return null;
        }
    }

    /** Tes koneksi — untuk halaman admin. */
    public function testSearch(string $query = 'earth nature environment'): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'API Key atau Search Engine ID (CX) belum diisi.'];
        }

        $path = $this->fetch($query, 'test-'.time());
        if (! $path) {
            return ['ok' => false, 'message' => 'Pencarian gambar gagal. Pastikan Custom Search API aktif & CX mendukung image search.'];
        }

        return ['ok' => true, 'message' => 'Berhasil mengambil gambar dari Google ('.round(filesize($path) / 1024).' KB).'];
    }
}
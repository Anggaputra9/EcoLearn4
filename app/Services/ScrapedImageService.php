<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Cari gambar via scraping web (Bing Images utama, Google Images cadangan),
 * lalu unduh & simpan permanen ke storage publik.
 */
class ScrapedImageService
{
    public const DISK = 'public';

    protected string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    /**
     * Scrape URL gambar, unduh, simpan ke path relatif storage publik.
     *
     * @return string|null Path relatif (mis. material-presentations/.../images/img-01.jpg)
     */
    public function fetchToStorage(string $query, string $relativePath): ?string
    {
        $query = trim(preg_replace('/\s+/', ' ', $query));
        if ($query === '') {
            return null;
        }

        $disk = Storage::disk(self::DISK);
        if ($disk->exists($relativePath) && $disk->size($relativePath) > 2048) {
            return $relativePath;
        }

        $disk->makeDirectory(dirname($relativePath));

        foreach ($this->searchImageUrls($query) as $url) {
            $bytes = $this->downloadBytes($url);
            if ($bytes === null) {
                continue;
            }

            $disk->put($relativePath, $bytes);

            return $relativePath;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public function searchImageUrls(string $query): array
    {
        $urls = array_merge(
            $this->scrapeBingImages($query),
            $this->scrapeGoogleImages($query)
        );

        $seen = [];
        $out = [];
        foreach ($urls as $url) {
            $url = $this->normalizeUrl($url);
            if ($url === '' || isset($seen[$url])) {
                continue;
            }
            if (! $this->looksLikeImageUrl($url)) {
                continue;
            }
            $seen[$url] = true;
            $out[] = $url;
            if (count($out) >= 12) {
                break;
            }
        }

        return $out;
    }

    /**
     * Scrape Bing Images — paling stabil dari server.
     *
     * @return array<int, string>
     */
    protected function scrapeBingImages(string $query): array
    {
        try {
            $res = Http::timeout(15)
                ->withHeaders([
                    'User-Agent'      => $this->userAgent,
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get('https://www.bing.com/images/search', [
                    'q'    => $query,
                    'form' => 'HDRSC2',
                    'first'=> 1,
                ]);

            if (! $res->successful()) {
                return [];
            }

            $body = $res->body();
            $urls = [];

            if (preg_match_all('#murl&quot;:&quot;(https?://[^&]+)#i', $body, $m)) {
                $urls = array_merge($urls, $m[1]);
            }
            if (preg_match_all('#"murl":"(https?://[^"]+)"#i', $body, $m)) {
                $urls = array_merge($urls, $m[1]);
            }
            if (preg_match_all('#imgurl:(https?://[^&"]+)#i', $body, $m)) {
                $urls = array_merge($urls, array_map('urldecode', $m[1]));
            }

            return $urls;
        } catch (\Throwable $e) {
            Log::info('ScrapedImageService Bing scrape gagal: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Scrape Google Images (tbm=isch) — kadang diblokir dari server.
     *
     * @return array<int, string>
     */
    protected function scrapeGoogleImages(string $query): array
    {
        try {
            $res = Http::timeout(15)
                ->withHeaders([
                    'User-Agent'      => $this->userAgent,
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get('https://www.google.com/search', [
                    'q'   => $query,
                    'tbm' => 'isch',
                    'hl'  => 'en',
                ]);

            if (! $res->successful()) {
                return [];
            }

            $body = $res->body();
            $urls = [];

            if (preg_match_all('#"ou":"(https?://[^"]+)"#', $body, $m)) {
                foreach ($m[1] as $u) {
                    $urls[] = str_replace('\\/', '/', $u);
                }
            }

            if (preg_match_all('#\["(https?://[^"]+\.(?:jpe?g|png|webp)[^"]*)",\d+,\d+\]#i', $body, $m)) {
                foreach ($m[1] as $u) {
                    $urls[] = str_replace('\\/', '/', $u);
                }
            }

            if (preg_match_all('#imgurl=(https?[^&"]+)#i', $body, $m)) {
                foreach ($m[1] as $u) {
                    $urls[] = urldecode($u);
                }
            }

            return $urls;
        } catch (\Throwable $e) {
            Log::info('ScrapedImageService Google scrape gagal: '.$e->getMessage());

            return [];
        }
    }

    protected function downloadBytes(string $url): ?string
    {
        try {
            $res = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Referer'    => 'https://www.bing.com/',
                ])
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
            if ($type !== '' && ! str_contains($type, 'image') && ! str_contains($type, 'octet-stream')) {
                return null;
            }

            return $body;
        } catch (\Throwable $e) {
            Log::info('ScrapedImageService download gagal: '.$e->getMessage());

            return null;
        }
    }

    protected function normalizeUrl(string $url): string
    {
        $url = str_replace('\\/', '/', trim($url));
        $url = str_replace('\u0026', '&', $url);

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    protected function looksLikeImageUrl(string $url): bool
    {
        if (! str_starts_with($url, 'http')) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST) ?: '';
        if (str_contains($host, 'gstatic.com') && str_contains($url, 'encrypted')) {
            return true;
        }

        return (bool) preg_match('/\.(jpe?g|png|webp)(\?|$)/i', $url);
    }

    public function publicUrl(string $relativePath): string
    {
        return Storage::disk(self::DISK)->url($relativePath);
    }

    /** Tes untuk admin / debugging. */
    public function test(string $query = 'earth nature environment'): array
    {
        $rel = 'material-presentations/_test/scrape-'.time().'.jpg';
        $saved = $this->fetchToStorage($query, $rel);
        if (! $saved) {
            return ['ok' => false, 'message' => 'Scraping gambar gagal. Coba lagi atau ganti kata kunci.'];
        }

        $kb = round(Storage::disk(self::DISK)->size($saved) / 1024);

        return ['ok' => true, 'message' => "Berhasil scrape & simpan gambar ({$kb} KB).", 'path' => $saved];
    }
}
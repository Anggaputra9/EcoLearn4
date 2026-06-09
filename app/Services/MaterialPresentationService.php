<?php

namespace App\Services;

use App\Models\Material;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyusun, menyimpan, dan menayangkan presentasi HTML (slide deck)
 * hasil generate AI dengan Tailwind CSS.
 */
class MaterialPresentationService
{
    public const DISK = 'public';
    public const BASE_DIR = 'material-presentations';
    public const DRAFT_DIR = 'material-presentations/_drafts';

    /**
     * Prompt sistem khusus untuk deck HTML.
     */
    public function slidesSystemPrompt(): string
    {
        return 'Anda adalah desainer presentasi edukasi kreatif berbahasa Indonesia (ekoteologi). '
            .'Anda bebas menentukan layout, tipografi, warna, dan komposisi visual — asalkan HTML valid & Tailwind CSS. '
            .'Gambar bersifat OPSIONAL: pakai hanya jika memperkuat materi. Jangan bungkus dengan markdown.';
    }

    /**
     * Prompt user untuk menghasilkan deck HTML.
     */
    public function slidesUserPrompt(
        string $title,
        string $topic,
        string $level,
        string $customPrompt = ''
    ): string {
        $base = "Topik utama: \"{$topic}\".\nJudul materi: \"{$title}\".\nTingkat sasaran: {$level}.";
        if ($customPrompt !== '') {
            $base .= "\nArahan tambahan dari guru (WAJIB diikuti): {$customPrompt}";
        }

        return $base."\n\nTugas: buat PRESENTASI HTML interaktif (8-12 slide) untuk guru mempresentasikan di kelas.\n\n"
            ."KEBEBASAN DESAIN:\n"
            ."- Anda bebas menentukan gaya visual (warna, grid, kartu, gradien, tipografi) selama elegan & mudah dibaca.\n"
            ."- Tidak wajib pakai gambar di setiap slide. Tambahkan gambar hanya jika relevan & memperkaya pesan.\n"
            ."- Jika ingin gambar, gunakan placeholder (JANGAN isi src — sistem scrape & simpan ke server):\n"
            ."  a) <img data-image-query=\"English search terms\" alt=\"...\" class=\"...tailwind...\" />\n"
            ."  b) <div data-image-query=\"English search terms\" class=\"...tailwind...\"></div> (div KOSONG, tanpa anak elemen)\n"
            ."  Query Bahasa Inggris, spesifik (contoh: tropical rainforest aerial view). Boleh tanpa gambar sama sekali.\n\n"
            ."ATURAN TEKNIS:\n"
            ."1. Output HANYA HTML dari <!DOCTYPE html> sampai </html>.\n"
            ."2. <head> wajib ada: <script src=\"https://cdn.tailwindcss.com\"></script>\n"
            ."3. Tiap slide: <section data-slide=\"N\" class=\"slide hidden ...\"> — slide nonaktif pakai class hidden.\n"
            ."4. Slide 1 = sampul; slide terakhir = penutup/refleksi.\n"
            ."5. Tiap slide isi: judul (h1/h2), poin-poin materi, <aside data-notes class=\"sr-only\">catatan pengajar</aside>.\n"
            ."6. JANGAN buat tombol/navigasi slide (Sebelumnya/Berikutnya/Prev/Next) — sistem inject otomatis.\n"
            ."7. Bahasa Indonesia baku, ramah pelajar, tanpa emoji. Responsif untuk proyektor & mobile.";
    }

    /**
     * Generate deck HTML via AI.
     */
    public function generateSlidesHtml(
        AIService $ai,
        string $title,
        string $topic,
        string $level,
        string $customPrompt = '',
        string $deckId = '',
        int $userId = 0
    ): string {
        $raw = $ai->generateText(
            $this->slidesUserPrompt($title, $topic, $level, $customPrompt),
            $this->slidesSystemPrompt()
        );

        $html = $this->extractHtml((string) $raw);
        if ($html === '') {
            throw new \RuntimeException('AI tidak mengembalikan HTML presentasi yang valid.');
        }

        if ($deckId !== '' && $userId > 0) {
            $html = $this->resolveImagePlaceholders($html, $this->draftBaseDir($userId, $deckId));
        }

        $html = $this->repairSlideDeck($html);
        $html = $this->normalizeHtml($html);

        return $html;
    }

    public function draftBaseDir(int $userId, string $deckId): string
    {
        return self::DRAFT_DIR.'/'.$userId.'/'.$deckId;
    }

    public function deckHtmlRelative(string $baseDir): string
    {
        return rtrim($baseDir, '/').'/deck.html';
    }

    /**
     * Scrape gambar dari web, unduh ke folder deck, sisipkan URL storage lokal.
     */
    public function resolveImagePlaceholders(string $html, string $deckBaseDir): string
    {
        $scraper = app(ScrapedImageService::class);
        $imgIdx = 0;

        $html = preg_replace_callback(
            '/<img\b([^>]*?)data-(?:google-query|image-query)\s*=\s*["\']([^"\']+)["\']([^>]*)>/iu',
            function (array $m) use ($scraper, $deckBaseDir, &$imgIdx) {
                $imgIdx++;
                $query = trim($m[2]);
                $attrs = $m[1].$m[3];
                $url = $this->storeScrapedImage($scraper, $query, $deckBaseDir, 'img-'.$imgIdx);
                if (! $url) {
                    return '<img'.$attrs.' data-image-query="'.htmlspecialchars($query, ENT_QUOTES, 'UTF-8').'" />';
                }

                $attrs = preg_replace('/\s*data-(?:google-query|image-query)\s*=\s*["\'][^"\']*["\']/iu', '', $attrs) ?? $attrs;
                $attrs = preg_replace('/\bsrc\s*=\s*["\'][^"\']*["\']/iu', '', $attrs) ?? $attrs;

                return '<img'.$attrs.' src="'.$url.'" data-image-resolved="1" loading="lazy" />';
            },
            $html
        ) ?? $html;

        // Hanya placeholder div/figure KOSONG — hindari regex nested div yang merusak struktur slide.
        $boxIdx = 0;
        $html = preg_replace_callback(
            '/<(div|figure)\b([^>]*?)data-(?:google-image|image-query)\s*=\s*["\']([^"\']+)["\']([^>]*?)>\s*<\/\1>/is',
            function (array $m) use ($scraper, $deckBaseDir, &$boxIdx) {
                $boxIdx++;
                $tag = $m[1];
                $query = trim($m[3]);
                $openAttrs = $m[2].$m[4];

                $url = $this->storeScrapedImage($scraper, $query, $deckBaseDir, 'box-'.$boxIdx);
                if (! $url) {
                    return '<'.$tag.$openAttrs.' data-image-query="'.htmlspecialchars($query, ENT_QUOTES, 'UTF-8').'"></'.$tag.'>';
                }

                $alt = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
                $openAttrs = preg_replace('/\s*data-(?:google-image|image-query)\s*=\s*["\'][^"\']*["\']/iu', '', $openAttrs) ?? $openAttrs;

                return '<'.$tag.$openAttrs.'>'
                    .'<img src="'.$url.'" alt="'.$alt.'" data-image-resolved="1" class="w-full h-full object-cover" loading="lazy" />'
                    .'</'.$tag.'>';
            },
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * Perbaiki struktur deck setelah injeksi gambar: pastikan tiap slide utuh & terpisah.
     */
    public function repairSlideDeck(string $html): string
    {
        $html = $this->stripInjectedNav($html);

        return $html;
    }

    public function prepareForDisplay(string $html): string
    {
        return $this->normalizeHtml($this->repairSlideDeck($html));
    }

    protected function stripInjectedNav(string $html): string
    {
        $html = preg_replace('/<div id="ecolearn-slide-nav"[^>]*>.*?<\/div>/is', '', $html) ?? $html;
        $html = preg_replace('/<script[^>]*data-slide-nav[^>]*>.*?<\/script>/is', '', $html) ?? $html;

        return $this->stripAiNavigation($html);
    }

    /**
     * Hapus navigasi buatan AI supaya tidak dobel dengan nav sistem.
     */
    protected function stripAiNavigation(string $html): string
    {
        $html = preg_replace('/<button[^>]*\bid\s*=\s*["\']slide-prev["\'][^>]*>.*?<\/button>/is', '', $html) ?? $html;
        $html = preg_replace('/<button[^>]*\bid\s*=\s*["\']slide-next["\'][^>]*>.*?<\/button>/is', '', $html) ?? $html;
        $html = preg_replace('/<span[^>]*\bid\s*=\s*["\']slide-indicator["\'][^>]*>.*?<\/span>/is', '', $html) ?? $html;

        // Hapus bar navigasi fixed-bottom milik AI yang sudah kosong / hanya sisa indikator.
        $html = preg_replace(
            '/<(?:div|nav)\b[^>]*class="[^"]*\b(?:fixed|bottom)\b[^"]*"[^>]*>\s*(?:<span[^>]*>[\s\d\/]*<\/span>\s*)?<\/(?:div|nav)>/is',
            '',
            $html
        ) ?? $html;

        // Skrip navigasi buatan AI (bukan milik sistem).
        $html = preg_replace_callback(
            '/<script(?![^>]*data-slide-nav)\b[^>]*>([\s\S]*?)<\/script>/i',
            function (array $m) {
                $body = $m[1];
                if (preg_match('/slide-prev|slide-next|slide-indicator|data-slide/i', $body)) {
                    return '';
                }

                return $m[0];
            },
            $html
        ) ?? $html;

        return $html;
    }

    protected function storeScrapedImage(
        ScrapedImageService $scraper,
        string $query,
        string $deckBaseDir,
        string $filename
    ): ?string {
        $relative = rtrim($deckBaseDir, '/').'/images/'.$filename.'.jpg';
        $saved = $scraper->fetchToStorage($query, $relative);

        return $saved ? $scraper->publicUrl($saved) : null;
    }

    /**
     * Ambil HTML dari respons AI (bisa dibungkus markdown).
     */
    public function extractHtml(string $raw): string
    {
        $raw = trim($raw);

        if (preg_match('/```(?:html)?\s*(<!DOCTYPE[\s\S]*?<\/html>)\s*```/iu', $raw, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/(<!DOCTYPE[\s\S]*?<\/html>)/iu', $raw, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/(<html[\s\S]*?<\/html>)/iu', $raw, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    public function isHtml(string $content): bool
    {
        $trim = trim($content);

        return str_starts_with(strtolower($trim), '<!doctype')
            || str_starts_with(strtolower($trim), '<html')
            || (str_contains($trim, '<section') && str_contains($trim, 'data-slide'));
    }

    /**
     * Pastikan Tailwind CDN & skrip navigasi dasar ada.
     */
    public function normalizeHtml(string $html): string
    {
        $html = $this->stripInjectedNav($html);

        if (! preg_match('/cdn\.tailwindcss\.com/i', $html)) {
            $html = preg_replace(
                '/<head([^>]*)>/i',
                '<head$1>'."\n".'  <script src="https://cdn.tailwindcss.com"></script>',
                $html,
                1
            ) ?? $html;
        }

        $nav = $this->defaultNavUi().$this->defaultNavScript();
        if (preg_match('/<\/body>/i', $html)) {
            $html = preg_replace('/<\/body>/i', $nav."\n</body>", $html, 1) ?? $html;
        } else {
            $html .= $nav;
        }

        return $html;
    }

    protected function defaultNavUi(): string
    {
        return <<<'HTML'
<div id="ecolearn-slide-nav" class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-4 py-2 rounded-full bg-slate-900/80 text-white shadow-lg backdrop-blur-sm text-sm">
  <button type="button" id="slide-prev" class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 transition">← Sebelumnya</button>
  <span id="slide-indicator" class="min-w-[5rem] text-center tabular-nums text-xs">1 / 1</span>
  <button type="button" id="slide-next" class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 transition">Berikutnya →</button>
</div>
HTML;
    }

    /**
     * Navigasi slide yang konsisten — sort by data-slide, toggle hidden.
     */
    protected function defaultNavScript(): string
    {
        return <<<'JS'
<script data-slide-nav>
(function () {
  const slides = Array.from(document.querySelectorAll('section[data-slide]'))
    .sort((a, b) => (parseInt(a.dataset.slide, 10) || 0) - (parseInt(b.dataset.slide, 10) || 0));
  if (!slides.length) return;

  let idx = 0;
  function show(i) {
    idx = Math.max(0, Math.min(slides.length - 1, i));
    slides.forEach((s, n) => {
      s.classList.toggle('hidden', n !== idx);
      s.style.display = n === idx ? '' : 'none';
    });
    const el = document.getElementById('slide-indicator');
    if (el) el.textContent = (idx + 1) + ' / ' + slides.length;
  }

  document.getElementById('slide-prev')?.addEventListener('click', () => show(idx - 1));
  document.getElementById('slide-next')?.addEventListener('click', () => show(idx + 1));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight' || e.key === 'PageDown') show(idx + 1);
    if (e.key === 'ArrowLeft' || e.key === 'PageUp') show(idx - 1);
  });

  show(0);
})();
</script>
JS;
    }

    /**
     * Simpan draft HTML sementara (sebelum materi disimpan ke DB).
     *
     * @return array{path:string, uuid:string}
     */
    public function saveDraft(int $userId, string $html, string $deckId): array
    {
        $base = $this->draftBaseDir($userId, $deckId);
        $relative = $this->deckHtmlRelative($base);
        Storage::disk(self::DISK)->makeDirectory($base.'/images');
        Storage::disk(self::DISK)->put($relative, $this->normalizeHtml($html));

        return ['path' => $relative, 'uuid' => $deckId];
    }

    public function isDraftPath(?string $path): bool
    {
        return $path && str_contains($path, '/_drafts/');
    }

    /**
     * Simpan HTML ke storage publik.
     *
     * @return array{path:string, uuid:string, slug:string}
     */
    public function save(string $title, string $html, ?string $existingPath = null): array
    {
        $slug = Str::slug($title) ?: 'materi';

        if ($existingPath && $this->isDraftPath($existingPath)) {
            return $this->promoteDraftDeck($title, $html, $existingPath);
        }

        if ($existingPath && Storage::disk(self::DISK)->exists($existingPath)) {
            Storage::disk(self::DISK)->put($existingPath, $html);
            $deckId = $this->deckIdFromHtmlPath($existingPath);

            return [
                'path' => $existingPath,
                'uuid' => $deckId,
                'slug' => basename(dirname($existingPath)),
            ];
        }

        $deckId = (string) Str::uuid();
        $base = self::BASE_DIR.'/'.$slug.'/'.$deckId;
        $relative = $this->deckHtmlRelative($base);
        Storage::disk(self::DISK)->makeDirectory($base.'/images');
        Storage::disk(self::DISK)->put($relative, $html);

        return ['path' => $relative, 'uuid' => $deckId, 'slug' => $slug];
    }

    /**
     * Pindahkan draft (HTML + folder images) ke lokasi final.
     *
     * @return array{path:string, uuid:string, slug:string}
     */
    protected function promoteDraftDeck(string $title, string $html, string $draftHtmlPath): array
    {
        $slug = Str::slug($title) ?: 'materi';
        $deckId = $this->deckIdFromHtmlPath($draftHtmlPath);
        $draftBase = dirname($draftHtmlPath);
        $finalBase = self::BASE_DIR.'/'.$slug.'/'.$deckId;
        $finalHtml = $this->deckHtmlRelative($finalBase);

        $html = str_replace(
            Storage::disk(self::DISK)->url($draftBase),
            Storage::disk(self::DISK)->url($finalBase),
            $html
        );

        $disk = Storage::disk(self::DISK);
        $disk->makeDirectory($finalBase.'/images');

        if ($disk->exists($draftBase.'/images')) {
            foreach ($disk->files($draftBase.'/images') as $file) {
                $name = basename($file);
                $disk->put($finalBase.'/images/'.$name, $disk->get($file));
            }
        }

        $disk->put($finalHtml, $this->normalizeHtml($html));
        $this->deleteDeckDirectory($draftHtmlPath);

        return ['path' => $finalHtml, 'uuid' => $deckId, 'slug' => $slug];
    }

    public function deckIdFromHtmlPath(string $htmlPath): string
    {
        $name = pathinfo($htmlPath, PATHINFO_FILENAME);

        return $name === 'deck' ? basename(dirname($htmlPath)) : $name;
    }

    public function delete(?string $path): void
    {
        $this->deleteDeckDirectory($path);
    }

    public function deleteDeckDirectory(?string $htmlPath): void
    {
        if (! $htmlPath) {
            return;
        }

        $disk = Storage::disk(self::DISK);

        if (pathinfo($htmlPath, PATHINFO_FILENAME) === 'deck') {
            $deckDir = dirname($htmlPath);
            if ($disk->exists($deckDir)) {
                $disk->deleteDirectory($deckDir);
            }

            return;
        }

        if ($disk->exists($htmlPath)) {
            $disk->delete($htmlPath);
        }
    }

    public function read(?string $path): ?string
    {
        if (! $path || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->get($path);
    }

    public function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    /**
     * Ringkasan teks singkat untuk kolom content (bukan HTML penuh).
     */
    public function summarize(string $html, string $title): string
    {
        $count = $this->countSlides($html);

        $photos = preg_match_all('/data-image-resolved\s*=\s*["\']1["\']/i', $html) ?: 0;
        $imgNote = $photos > 0 ? " dengan {$photos} gambar tersimpan" : '';

        return "Presentasi HTML{$imgNote}: {$title} ({$count} slide). Buka pratinjau untuk menampilkan deck.";
    }

    public function countSlides(string $html): int
    {
        if (preg_match_all('/<section[^>]+data-slide\s*=/iu', $html, $m)) {
            return max(1, count($m[0]));
        }
        if (preg_match_all('/<section[^>]+class\s*=\s*"[^"]*slide/iu', $html, $m)) {
            return max(1, count($m[0]));
        }

        return 1;
    }

    /**
     * Parse HTML deck ke struktur slide untuk ekspor PPTX/PDF.
     *
     * @return array<int, array{title:string, bullets:array<int,string>, notes:string}>
     */
    public function parseHtmlSlides(string $html): array
    {
        $slides = [];
        if (! preg_match_all('/<section[^>]*>(.*?)<\/section>/is', $html, $sections)) {
            return [];
        }

        foreach ($sections[1] as $body) {
            $title = '';
            if (preg_match('/<h[12][^>]*>(.*?)<\/h[12]>/is', $body, $m)) {
                $title = trim(strip_tags($m[1]));
            }

            $bullets = [];
            if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $body, $lis)) {
                foreach ($lis[1] as $li) {
                    $t = trim(strip_tags($li));
                    if ($t !== '') {
                        $bullets[] = $t;
                    }
                }
            }

            $notes = '';
            if (preg_match('/<aside[^>]*data-notes[^>]*>(.*?)<\/aside>/is', $body, $m)) {
                $notes = trim(strip_tags($m[1]));
            }

            if ($title === '' && empty($bullets)) {
                continue;
            }

            $slides[] = [
                'title'   => $title ?: 'Slide',
                'bullets' => $bullets,
                'notes'   => $notes,
            ];
        }

        return $slides;
    }

    /**
     * Cari path HTML slides dari outputs material.
     */
    public function findSlidesPath(Material $material): ?string
    {
        foreach ((array) $material->outputs as $out) {
            if (($out['format'] ?? '') === 'slides' && ! empty($out['html_path'])) {
                return (string) $out['html_path'];
            }
        }

        if ($material->format === 'slides' && is_string($material->content) && $this->isHtml($material->content)) {
            return null; // legacy inline HTML, belum difile
        }

        return null;
    }

    /**
     * Hapus semua file presentasi yang direferensikan outputs material.
     */
    public function deleteAllForMaterial(Material $material): void
    {
        $paths = [];
        foreach ((array) $material->outputs as $out) {
            if (! empty($out['html_path'])) {
                $paths[] = (string) $out['html_path'];
            }
        }
        foreach (array_unique($paths) as $path) {
            $this->delete($path);
        }
    }

    /**
     * Proses satu baris output slides: simpan HTML, kembalikan metadata.
     *
     * @param  array{format?:string, label?:string, content?:string, html_content?:string, html_path?:string}  $row
     * @return array{format:string, label:string, content:string, html_path?:string}
     */
    public function processSlidesOutput(array $row, string $title): array
    {
        $fmt = (string) ($row['format'] ?? 'slides');
        $label = trim((string) ($row['label'] ?? Material::formatLabel('slides'))) ?: Material::formatLabel('slides');
        $existingPath = ! empty($row['html_path']) ? (string) $row['html_path'] : null;

        $html = trim((string) ($row['html_content'] ?? ''));
        if ($html === '') {
            $content = trim((string) ($row['content'] ?? ''));
            if ($this->isHtml($content)) {
                $html = $this->normalizeHtml($content);
            } elseif ($existingPath && Storage::disk(self::DISK)->exists($existingPath)) {
                $html = (string) $this->read($existingPath);
            }
        } else {
            $html = $this->normalizeHtml($html);
        }

        if ($html !== '') {
            $saved = $this->save($title, $html, $existingPath);

            return [
                'format'    => $fmt,
                'label'     => $label,
                'content'   => $this->summarize($html, $title),
                'html_path' => $saved['path'],
            ];
        }

        // Pertahankan file lama jika hanya metadata teks yang dikirim ulang.
        if ($existingPath && Storage::disk(self::DISK)->exists($existingPath)) {
            return [
                'format'    => $fmt,
                'label'     => $label,
                'content'   => trim((string) ($row['content'] ?? '')) ?: $this->summarize(
                    (string) $this->read($existingPath),
                    $title
                ),
                'html_path' => $existingPath,
            ];
        }

        $txt = trim((string) ($row['content'] ?? ''));
        if ($txt === '') {
            return [
                'format'  => $fmt,
                'label'   => $label,
                'content' => '',
            ];
        }

        return [
            'format'  => $fmt,
            'label'   => $label,
            'content' => $txt,
        ];
    }
}
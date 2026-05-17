<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use League\CommonMark\CommonMarkConverter;

/**
 * Membaca dan mem-parsing CHANGELOG.md (Keep a Changelog) dari root proyek.
 *
 * Sumber kebenaran tunggal untuk halaman /admin/changelogs dan widget
 * "Rilis Terbaru" di dashboard. Tidak ada penyimpanan ke database.
 */
class ChangelogService
{
    protected string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? base_path('CHANGELOG.md');
    }

    /**
     * Semua entri rilis, sudah ter-urut dari yang terbaru.
     *
     * @return Collection<int, object>
     */
    public function all(): Collection
    {
        if (! is_file($this->path)) {
            return collect();
        }

        return collect($this->parse((string) file_get_contents($this->path)));
    }

    /**
     * N rilis terbaru.
     *
     * @return Collection<int, object>
     */
    public function recent(int $limit = 3): Collection
    {
        return $this->all()->take($limit);
    }

    /**
     * Pencarian + filter by kind (major/minor/patch/hotfix).
     *
     * @return Collection<int, object>
     */
    public function search(?string $q = null, ?string $kind = null): Collection
    {
        $q = trim((string) $q);
        $kind = trim((string) $kind);

        return $this->all()->filter(function ($entry) use ($q, $kind) {
            if ($kind !== '' && $entry->kind !== $kind) {
                return false;
            }
            if ($q === '') {
                return true;
            }
            $needle = mb_strtolower($q);
            return str_contains(mb_strtolower($entry->version), $needle)
                || str_contains(mb_strtolower($entry->notes), $needle);
        })->values();
    }

    /**
     * Parse isi markdown menjadi list entri rilis.
     *
     * @return array<int, object>
     */
    protected function parse(string $raw): array
    {
        $lines = preg_split('/\r\n|\n|\r/', $raw) ?: [];

        $entries = [];
        $current = null;
        $body = [];

        foreach ($lines as $line) {
            // Cocokkan: "## [0.3.0] – 2026-05-20" (en/em dash, hyphen, atau tanpa pemisah)
            if (preg_match('/^##\s*\[([^\]]+)\]\s*(?:[\x{2013}\x{2014}\-]\s*(.+))?$/u', $line, $m)) {
                if ($current !== null) {
                    $current['notes'] = trim(implode("\n", $body));
                    $entries[] = $current;
                }
                $current = [
                    'version'  => trim($m[1]),
                    'date_raw' => trim($m[2] ?? ''),
                ];
                $body = [];
                continue;
            }

            if ($current !== null) {
                // Lewati horizontal rule pemisah antar entri
                if (rtrim($line) === '---') {
                    continue;
                }
                $body[] = $line;
            }
        }

        if ($current !== null) {
            $current['notes'] = trim(implode("\n", $body));
            $entries[] = $current;
        }

        $converter = new CommonMarkConverter([
            'html_input'         => 'escape',
            'allow_unsafe_links' => false,
        ]);

        $entries = array_map(function (array $e) use ($converter) {
            $released = null;
            if ($e['date_raw'] !== '') {
                try {
                    $released = Carbon::parse($e['date_raw']);
                } catch (\Throwable) {
                    $released = null;
                }
            }

            return (object) [
                'version'     => $e['version'],
                'released_at' => $released,
                'kind'        => $this->deriveKind($e['version']),
                'notes'       => $e['notes'],
                'html'        => (string) $converter->convert($e['notes']),
            ];
        }, $entries);

        // Urutkan terbaru dulu (berdasarkan tanggal, fallback semver)
        usort($entries, function ($a, $b) {
            $ta = $a->released_at?->getTimestamp() ?? 0;
            $tb = $b->released_at?->getTimestamp() ?? 0;
            if ($ta !== $tb) {
                return $tb <=> $ta;
            }
            return version_compare($b->version, $a->version);
        });

        return $entries;
    }

    /**
     * Tentukan jenis rilis dari nomor versi (heuristik semver).
     */
    protected function deriveKind(string $version): string
    {
        $clean = preg_replace('/[^0-9.]/', '', $version) ?? '';
        $parts = array_values(array_filter(explode('.', $clean), fn ($p) => $p !== ''));
        $major = (int) ($parts[0] ?? 0);
        $minor = (int) ($parts[1] ?? 0);
        $patch = (int) ($parts[2] ?? 0);

        if ($major > 0 && $minor === 0 && $patch === 0) {
            return 'major';
        }
        if ($patch > 0) {
            return 'patch';
        }
        return 'minor';
    }
}

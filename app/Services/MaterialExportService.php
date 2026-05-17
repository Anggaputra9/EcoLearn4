<?php

namespace App\Services;

use App\Models\Material;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * MaterialExportService — mengubah hasil AI berbasis teks menjadi
 * artefak siap pakai:
 *
 *   1. PPTX asli (bisa dibuka di PowerPoint / Google Slides)
 *   2. PDF bergaya slide (1 halaman = 1 slide)
 *   3. PDF bergaya infografis (1 halaman, blok visual)
 *
 * Gambar diambil otomatis dari LoremFlickr (tanpa API key) dengan
 * fallback ke Picsum supaya hasilnya selalu ada walau koneksi rewel.
 *
 * PPTX dirakit manual via ZipArchive — PPTX hanya .zip berisi XML
 * sesuai spesifikasi OpenXML. Tidak butuh dependency tambahan di
 * composer.json.
 */
class MaterialExportService
{
    protected int $imgWidth = 800;
    protected int $imgHeight = 450;

    /* ============================================================
     * PARSER
     * ============================================================ */

    /**
     * Parse teks slide hasil AI menjadi struktur data per slide.
     *
     * Format yang dipahami:
     *   Slide 1: Judul Slide
     *   - bullet 1
     *   - bullet 2
     *   Catatan Pengajar: catatan opsional
     *
     * @return array<int, array{title:string, bullets:array<int,string>, notes:string}>
     */
    public function parseSlides(string $text): array
    {
        $text = trim(preg_replace("/\r\n|\r/", "\n", $text));
        $slides = [];
        $current = null;
        $collectingNotes = false;

        // Cari posisi "Slide 1:" pertama; kalau ada, abaikan kalimat pengantar
        // sebelum itu agar slide pertama tidak ketukar dengan basa-basi AI.
        if (preg_match('/^\s*slide\s*\d+\s*[:\-–—]/imu', $text, $m, PREG_OFFSET_CAPTURE)) {
            $text = substr($text, $m[0][1]);
        }

        foreach (explode("\n", $text) as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') {
                $collectingNotes = false;
                continue;
            }

            // "Slide N: Judul" atau "Slide N - Judul"
            if (preg_match('/^slide\s*\d+\s*[:\-–—]\s*(.+)$/iu', $line, $m)) {
                if ($current) $slides[] = $current;
                $current = ['title' => trim($m[1]), 'bullets' => [], 'notes' => ''];
                $collectingNotes = false;
                continue;
            }

            if (! $current) {
                // Tidak ada penanda "Slide N:" sama sekali — fallback: pakai
                // baris pertama sebagai judul slide tunggal.
                $current = ['title' => $line, 'bullets' => [], 'notes' => ''];
                continue;
            }

            // Catatan pengajar
            if (preg_match('/^(catatan\s+pengajar|notes?)\s*[:\-]\s*(.+)$/iu', $line, $m)) {
                $current['notes'] = trim($m[2]);
                $collectingNotes = true;
                continue;
            }

            // Bullet
            if (preg_match('/^[\-\*•·]\s*(.+)$/u', $line, $m)) {
                $current['bullets'][] = trim($m[1]);
                $collectingNotes = false;
                continue;
            }

            // Lanjutan catatan multi-baris
            if ($collectingNotes) {
                $current['notes'] .= ' '.$line;
                continue;
            }

            // Baris bebas → masukkan sebagai bullet polos.
            $current['bullets'][] = $line;
        }

        if ($current) $slides[] = $current;

        return $slides;
    }


    /**
     * Parse naskah infografis menjadi struktur blok.
     *
     * @return array{
     *     title:string, subtitle:string,
     *     blocks: array<int, array{title:string, fact:string, data:string, quote:string}>,
     *     cta:string
     * }
     */
    public function parseInfographic(string $text): array
    {
        $text = trim(preg_replace("/\r\n|\r/", "\n", $text));
        $title = '';
        $subtitle = '';
        $cta = '';
        $blocks = [];
        $current = null;

        foreach (explode("\n", $text) as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') continue;

            if (preg_match('/^judul\s+utama\s*[:\-]\s*(.+)$/iu', $line, $m)) { $title = $m[1]; continue; }
            if (preg_match('/^subjudul\s*[:\-]\s*(.+)$/iu', $line, $m))     { $subtitle = $m[1]; continue; }
            if (preg_match('/^ajakan\s+aksi\s*[:\-]\s*(.+)$/iu', $line, $m)) { $cta = $m[1]; continue; }

            if (preg_match('/^blok\s*\d+\s*[—\-:–]\s*(.+)$/iu', $line, $m)) {
                if ($current) $blocks[] = $current;
                $current = ['title' => trim($m[1]), 'fact' => '', 'data' => '', 'quote' => ''];
                continue;
            }

            if (! $current) continue;

            if (preg_match('/^fakta\s+kunci\s*[:\-]\s*(.+)$/iu', $line, $m))   { $current['fact'] = trim($m[1]); continue; }
            if (preg_match('/^(data|statistik|data\/statistik)[^:\-]*[:\-]\s*(.+)$/iu', $line, $m)) { $current['data'] = trim($m[2]); continue; }
            if (preg_match('/^kutipan[^:\-]*[:\-]\s*(.+)$/iu', $line, $m))     { $current['quote'] = trim($m[1]); continue; }

            // Append ke fact kalau belum ada.
            if ($current['fact'] === '') $current['fact'] = $line;
            else $current['fact'] .= ' '.$line;
        }
        if ($current) $blocks[] = $current;

        return compact('title', 'subtitle', 'blocks', 'cta');
    }

    /* ============================================================
     * IMAGE FETCHER
     * ============================================================ */

    /**
     * Ambil gambar berdasarkan kata kunci, kembalikan path file lokal
     * (cache di storage/app/material-images). Jika gagal total → null.
     */
    public function fetchImage(string $keyword, string $cacheKey): ?string
    {
        $dir = storage_path('app/material-images');
        if (! is_dir($dir)) @mkdir($dir, 0777, true);

        $path = $dir.DIRECTORY_SEPARATOR.$cacheKey.'.jpg';
        if (is_file($path) && filesize($path) > 1024) {
            return $path;
        }

        $kw = trim(preg_replace('/[^A-Za-z0-9, ]/', ' ', Str::ascii($keyword)));
        $kw = preg_replace('/\s+/', ',', $kw);
        if ($kw === '') $kw = 'nature,environment';

        $sources = [
            "https://loremflickr.com/{$this->imgWidth}/{$this->imgHeight}/".rawurlencode($kw)."?lock=".crc32($cacheKey),
            "https://picsum.photos/seed/".rawurlencode($cacheKey)."/{$this->imgWidth}/{$this->imgHeight}",
        ];

        foreach ($sources as $url) {
            try {
                $res = Http::timeout(10)->withOptions(['allow_redirects' => true])->get($url);
                if ($res->ok() && strlen($res->body()) > 1024) {
                    file_put_contents($path, $res->body());
                    return $path;
                }
            } catch (\Throwable $e) {
                Log::info('fetchImage gagal '.$url.': '.$e->getMessage());
            }
        }
        return null;
    }

    /**
     * Cari format "infographic" / "slides" pada bundle outputs material.
     */
    public function findOutput(Material $material, string $format): ?string
    {
        foreach ($material->outputBundle() as $out) {
            if ($out['format'] === $format) {
                return $out['content'];
            }
        }
        // Fallback: kalau format yang diminta = standard, pakai content saja.
        return $format === 'standard' ? (string) $material->content : null;
    }

    /* ============================================================
     * PPTX BUILDER
     * ============================================================ */

    /**
     * Bangun file .pptx berisi seluruh slide. Kembalikan path file
     * sementara — pemanggil bertanggung jawab menghapus setelah dikirim.
     */
    public function buildPptx(Material $material): string
    {
        $raw = $this->findOutput($material, 'slides') ?: $this->findOutput($material, 'standard') ?: '';
        $slides = $this->parseSlides($raw);

        // Selalu ada minimal 1 slide judul.
        if (empty($slides)) {
            $slides = [[
                'title'   => $material->title,
                'bullets' => [$material->topic, 'Tingkat: '.$material->level],
                'notes'   => '',
            ]];
        } else {
            // Pastikan slide pertama sebagai sampul punya judul materi.
            if (! preg_match('/'.preg_quote($material->title, '/').'/iu', $slides[0]['title'])) {
                array_unshift($slides, [
                    'title'   => $material->title,
                    'bullets' => [$material->topic, 'Tingkat: '.$material->level],
                    'notes'   => '',
                ]);
            }
        }

        // Fetch gambar untuk tiap slide (kecuali slide tanpa bullets bisa skip).
        $images = [];
        foreach ($slides as $i => $slide) {
            $kw = $slide['title'].' '.$material->topic.' '.$material->level;
            $cacheKey = 'm'.$material->id.'-s'.$i.'-'.Str::slug(Str::limit($slide['title'], 40, ''));
            $images[$i] = $this->fetchImage($kw, $cacheKey);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pptx').'.pptx';
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Tidak bisa membuat file PPTX sementara.');
        }

        $totalSlides = count($slides);

        // Content Types
        $zip->addFromString('[Content_Types].xml', $this->pptxContentTypes($totalSlides, $images));

        // Top-level rels
        $zip->addFromString('_rels/.rels', $this->pptxRootRels());

        // Theme, master, layout
        $zip->addFromString('ppt/theme/theme1.xml', $this->pptxTheme());
        $zip->addFromString('ppt/slideMasters/slideMaster1.xml', $this->pptxSlideMaster());
        $zip->addFromString('ppt/slideMasters/_rels/slideMaster1.xml.rels', $this->pptxSlideMasterRels());
        $zip->addFromString('ppt/slideLayouts/slideLayout1.xml', $this->pptxSlideLayout());
        $zip->addFromString('ppt/slideLayouts/_rels/slideLayout1.xml.rels', $this->pptxSlideLayoutRels());

        // Presentation
        $zip->addFromString('ppt/presentation.xml', $this->pptxPresentation($totalSlides));
        $zip->addFromString('ppt/_rels/presentation.xml.rels', $this->pptxPresentationRels($totalSlides));
        $zip->addFromString('ppt/presProps.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:presentationPr xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"/>');
        $zip->addFromString('ppt/viewProps.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:viewPr xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"/>');
        $zip->addFromString('ppt/tableStyles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><a:tblStyleLst xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" def="{5C22544A-7EE6-4342-B048-85BDC9FD1C3A}"/>');

        // Slides + media
        foreach ($slides as $i => $slide) {
            $idx = $i + 1;
            $hasImage = ! empty($images[$i]);
            if ($hasImage) {
                $zip->addFile($images[$i], "ppt/media/image{$idx}.jpg");
            }
            $zip->addFromString("ppt/slides/slide{$idx}.xml", $this->pptxSlideXml($slide, $idx, $hasImage, $i === 0));
            $zip->addFromString("ppt/slides/_rels/slide{$idx}.xml.rels", $this->pptxSlideRels($idx, $hasImage));
        }

        $zip->close();
        return $tmp;
    }

    /* ---------- PPTX XML helpers ---------- */

    protected function pptxContentTypes(int $total, array $images): string
    {
        $overrides = '';
        for ($i = 1; $i <= $total; $i++) {
            $overrides .= '<Override PartName="/ppt/slides/slide'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'.
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'.
            '<Default Extension="xml" ContentType="application/xml"/>'.
            '<Default Extension="jpeg" ContentType="image/jpeg"/>'.
            '<Default Extension="jpg" ContentType="image/jpeg"/>'.
            '<Default Extension="png" ContentType="image/png"/>'.
            '<Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>'.
            '<Override PartName="/ppt/presProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presProps+xml"/>'.
            '<Override PartName="/ppt/viewProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.viewProps+xml"/>'.
            '<Override PartName="/ppt/tableStyles.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.tableStyles+xml"/>'.
            '<Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>'.
            '<Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/>'.
            '<Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>'.
            $overrides.
            '</Types>';
    }

    protected function pptxRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/>'.
            '</Relationships>';
    }

    protected function pptxPresentation(int $total): string
    {
        $sldIds = '';
        for ($i = 1; $i <= $total; $i++) {
            $rid = $i + 1; // rId1 = slideMaster, rId2.. = slides
            $sldId = 255 + $i;
            $sldIds .= '<p:sldId id="'.$sldId.'" r:id="rId'.$rid.'"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '.
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '.
            'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" saveSubsetFonts="1">'.
            '<p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rId1"/></p:sldMasterIdLst>'.
            '<p:sldIdLst>'.$sldIds.'</p:sldIdLst>'.
            // 16:9 widescreen 13.333"x7.5"
            '<p:sldSz cx="12192000" cy="6858000"/>'.
            '<p:notesSz cx="6858000" cy="9144000"/>'.
            '</p:presentation>';
    }

    protected function pptxPresentationRels(int $total): string
    {
        $items = '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>';
        for ($i = 1; $i <= $total; $i++) {
            $rid = $i + 1;
            $items .= '<Relationship Id="rId'.$rid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide'.$i.'.xml"/>';
        }
        $rid = $total + 2;
        $items .= '<Relationship Id="rId'.$rid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$items.'</Relationships>';
    }

    protected function pptxTheme(): string
    {
        // Tema minimal valid (ekstrak dari sample PowerPoint default).
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Eko-Scribe">'.
            '<a:themeElements>'.
            '<a:clrScheme name="Eko-Scribe">'.
            '<a:dk1><a:sysClr val="windowText" lastClr="000000"/></a:dk1>'.
            '<a:lt1><a:sysClr val="window" lastClr="FFFFFF"/></a:lt1>'.
            '<a:dk2><a:srgbClr val="064E3B"/></a:dk2>'.
            '<a:lt2><a:srgbClr val="ECFDF5"/></a:lt2>'.
            '<a:accent1><a:srgbClr val="10B981"/></a:accent1>'.
            '<a:accent2><a:srgbClr val="0D9488"/></a:accent2>'.
            '<a:accent3><a:srgbClr val="65A30D"/></a:accent3>'.
            '<a:accent4><a:srgbClr val="F59E0B"/></a:accent4>'.
            '<a:accent5><a:srgbClr val="0EA5E9"/></a:accent5>'.
            '<a:accent6><a:srgbClr val="8B5CF6"/></a:accent6>'.
            '<a:hlink><a:srgbClr val="0563C1"/></a:hlink>'.
            '<a:folHlink><a:srgbClr val="954F72"/></a:folHlink>'.
            '</a:clrScheme>'.
            '<a:fontScheme name="Eko-Scribe">'.
            '<a:majorFont><a:latin typeface="Calibri Light"/><a:ea typeface=""/><a:cs typeface=""/></a:majorFont>'.
            '<a:minorFont><a:latin typeface="Calibri"/><a:ea typeface=""/><a:cs typeface=""/></a:minorFont>'.
            '</a:fontScheme>'.
            '<a:fmtScheme name="Office">'.
            '<a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'.
            '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'.
            '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:fillStyleLst>'.
            '<a:lnStyleLst>'.
            '<a:ln w="9525" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln>'.
            '<a:ln w="9525" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln>'.
            '<a:ln w="9525" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln>'.
            '</a:lnStyleLst>'.
            '<a:effectStyleLst><a:effectStyle><a:effectLst/></a:effectStyle>'.
            '<a:effectStyle><a:effectLst/></a:effectStyle>'.
            '<a:effectStyle><a:effectLst/></a:effectStyle></a:effectStyleLst>'.
            '<a:bgFillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'.
            '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'.
            '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:bgFillStyleLst>'.
            '</a:fmtScheme>'.
            '</a:themeElements>'.
            '</a:theme>';
    }

    protected function pptxSlideMaster(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'.
            '<p:cSld><p:bg><p:bgRef idx="1001"><a:schemeClr val="bg1"/></p:bgRef></p:bg>'.
            '<p:spTree>'.
            '<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>'.
            '<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>'.
            '</p:spTree>'.
            '</p:cSld>'.
            '<p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/>'.
            '<p:sldLayoutIdLst><p:sldLayoutId id="2147483649" r:id="rId1"/></p:sldLayoutIdLst>'.
            '<p:txStyles>'.
            '<p:titleStyle><a:lvl1pPr algn="l"><a:defRPr sz="3200" b="1"><a:solidFill><a:schemeClr val="dk2"/></a:solidFill><a:latin typeface="+mj-lt"/></a:defRPr></a:lvl1pPr></p:titleStyle>'.
            '<p:bodyStyle><a:lvl1pPr><a:defRPr sz="1800"><a:solidFill><a:schemeClr val="tx1"/></a:solidFill><a:latin typeface="+mn-lt"/></a:defRPr></a:lvl1pPr></p:bodyStyle>'.
            '<p:otherStyle/>'.
            '</p:txStyles>'.
            '</p:sldMaster>';
    }

    protected function pptxSlideMasterRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>'.
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="../theme/theme1.xml"/>'.
            '</Relationships>';
    }

    protected function pptxSlideLayout(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="blank" preserve="1">'.
            '<p:cSld name="Blank">'.
            '<p:spTree>'.
            '<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>'.
            '<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>'.
            '</p:spTree>'.
            '</p:cSld>'.
            '</p:sldLayout>';
    }

    protected function pptxSlideLayoutRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/>'.
            '</Relationships>';
    }

    protected function pptxSlideRels(int $idx, bool $hasImage): string
    {
        $items = '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>';
        if ($hasImage) {
            $items .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image'.$idx.'.jpg"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$items.'</Relationships>';
    }

    /**
     * Render satu slide. Layout:
     *  - Bar judul hijau di atas
     *  - Bullet di kiri (50% jika ada gambar, 100% jika tidak)
     *  - Gambar di kanan (50%) jika ada
     *  - Catatan pengajar tampil di area "notes" PowerPoint (tidak terlihat di slideshow,
     *    tapi tersedia saat presentasi)
     */
    protected function pptxSlideXml(array $slide, int $idx, bool $hasImage, bool $isCover): string
    {
        $title = $this->xmlEscape($slide['title']);
        $bullets = $slide['bullets'] ?? [];

        // Slide size 12192000 x 6858000 (16:9)
        $titleBox = '<a:xfrm><a:off x="457200" y="365125"/><a:ext cx="11277600" cy="900000"/></a:xfrm>';
        $bodyOffX = 457200;
        $bodyW = $hasImage ? 5500000 : 11277600;
        $bodyBox = '<a:xfrm><a:off x="'.$bodyOffX.'" y="1400000"/><a:ext cx="'.$bodyW.'" cy="5000000"/></a:xfrm>';

        // Bar accent bawah judul
        $accent = '<p:sp>'.
            '<p:nvSpPr><p:cNvPr id="9" name="accent"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>'.
            '<p:spPr>'.
            '<a:xfrm><a:off x="457200" y="1180000"/><a:ext cx="1200000" cy="40000"/></a:xfrm>'.
            '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>'.
            '<a:solidFill><a:srgbClr val="10B981"/></a:solidFill>'.
            '<a:ln><a:noFill/></a:ln>'.
            '</p:spPr>'.
            '<p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody>'.
            '</p:sp>';

        // Title shape
        $titleSize = $isCover ? '4400' : '3200';
        $titleSp = '<p:sp>'.
            '<p:nvSpPr><p:cNvPr id="2" name="Title"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>'.
            '<p:spPr>'.$titleBox.'<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></p:spPr>'.
            '<p:txBody>'.
            '<a:bodyPr wrap="square" anchor="t"/>'.
            '<a:lstStyle/>'.
            '<a:p><a:pPr algn="l"/><a:r><a:rPr lang="id-ID" sz="'.$titleSize.'" b="1"><a:solidFill><a:srgbClr val="064E3B"/></a:solidFill><a:latin typeface="Calibri"/></a:rPr><a:t>'.$title.'</a:t></a:r></a:p>'.
            '</p:txBody>'.
            '</p:sp>';

        // Body shape (bullets)
        $bulletXml = '';
        foreach ($bullets as $b) {
            $b = $this->xmlEscape($b);
            $bulletXml .= '<a:p>'.
                '<a:pPr marL="285750" indent="-285750"><a:buFont typeface="Arial"/><a:buChar char="•"/></a:pPr>'.
                '<a:r><a:rPr lang="id-ID" sz="2000" dirty="0"><a:solidFill><a:srgbClr val="1F2937"/></a:solidFill><a:latin typeface="Calibri"/></a:rPr>'.
                '<a:t>'.$b.'</a:t></a:r></a:p>';
        }
        if ($bulletXml === '') {
            $bulletXml = '<a:p><a:r><a:rPr lang="id-ID" sz="2000"/><a:t> </a:t></a:r></a:p>';
        }

        $bodySp = '<p:sp>'.
            '<p:nvSpPr><p:cNvPr id="3" name="Body"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>'.
            '<p:spPr>'.$bodyBox.'<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></p:spPr>'.
            '<p:txBody>'.
            '<a:bodyPr wrap="square" anchor="t"/>'.
            '<a:lstStyle/>'.
            $bulletXml.
            '</p:txBody>'.
            '</p:sp>';

        // Image shape (kanan)
        $picSp = '';
        if ($hasImage) {
            $picSp = '<p:pic>'.
                '<p:nvPicPr>'.
                '<p:cNvPr id="4" name="Picture'.$idx.'"/>'.
                '<p:cNvPicPr><a:picLocks noChangeAspect="1"/></p:cNvPicPr>'.
                '<p:nvPr/>'.
                '</p:nvPicPr>'.
                '<p:blipFill>'.
                '<a:blip r:embed="rId2"/>'.
                '<a:stretch><a:fillRect/></a:stretch>'.
                '</p:blipFill>'.
                '<p:spPr>'.
                '<a:xfrm><a:off x="6300000" y="1400000"/><a:ext cx="5400000" cy="3037500"/></a:xfrm>'.
                '<a:prstGeom prst="roundRect"><a:avLst><a:gd name="adj" fmla="val 5000"/></a:avLst></a:prstGeom>'.
                '<a:ln w="12700"><a:solidFill><a:srgbClr val="10B981"/></a:solidFill></a:ln>'.
                '</p:spPr>'.
                '</p:pic>';
        }

        // Footer kecil
        $footerText = $this->xmlEscape('Slide '.$idx);
        $footerSp = '<p:sp>'.
            '<p:nvSpPr><p:cNvPr id="5" name="Footer"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>'.
            '<p:spPr><a:xfrm><a:off x="457200" y="6450000"/><a:ext cx="11277600" cy="300000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></p:spPr>'.
            '<p:txBody><a:bodyPr/><a:lstStyle/>'.
            '<a:p><a:pPr algn="r"/><a:r><a:rPr lang="id-ID" sz="1000"><a:solidFill><a:srgbClr val="6B7280"/></a:solidFill></a:rPr><a:t>'.$footerText.'</a:t></a:r></a:p>'.
            '</p:txBody></p:sp>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'.
            '<p:cSld>'.
            '<p:bg><p:bgPr><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:effectLst/></p:bgPr></p:bg>'.
            '<p:spTree>'.
            '<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>'.
            '<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>'.
            $titleSp.$accent.$bodySp.$picSp.$footerSp.
            '</p:spTree>'.
            '</p:cSld>'.
            '<p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>'.
            '</p:sld>';
    }

    protected function xmlEscape(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

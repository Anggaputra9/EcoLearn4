@php
    /**
     * Normalisasi konten materi agar PDF rapi:
     * - Ubah tab → spasi
     * - Hilangkan trailing/leading spasi tiap baris
     * - Kompres spasi berurutan (>=2) menjadi 1 (di dalam baris)
     * - Maks 1 baris kosong sebagai pemisah paragraf
     * - Pecah jadi paragraf untuk dirender sebagai <p>, agar text-align justify
     *   tidak menghasilkan "spasi panjang" akibat <pre>/pre-wrap.
     */
    $raw = (string) ($material->content ?? '');
    $raw = str_replace(["\r\n", "\r"], "\n", $raw);
    $raw = str_replace("\t", '    ', $raw);

    $lines = array_map(function ($line) {
        // Kompres spasi berurutan menjadi 1, lalu trim
        return trim(preg_replace('/[ \x{00A0}]{2,}/u', ' ', $line));
    }, explode("\n", $raw));

    // Maksimal 1 baris kosong berurutan
    $clean = [];
    $blank = false;
    foreach ($lines as $ln) {
        if ($ln === '') {
            if (! $blank) $clean[] = '';
            $blank = true;
        } else {
            $clean[] = $ln;
            $blank = false;
        }
    }
    $normalized = trim(implode("\n", $clean));

    // Pecah jadi paragraf berdasar baris kosong
    $paragraphs = preg_split('/\n\s*\n/', $normalized) ?: [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $material->title }}</title>
    <style>
        @page { margin: 22mm 18mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.6;
        }
        h1 { font-size: 22px; margin: 0 0 6px; color: #047857; }
        h2 { font-size: 14px; margin: 18px 0 8px; color: #0f766e; border-bottom: 1px solid #d1fae5; padding-bottom: 4px; }
        .meta { color: #6b7280; font-size: 11px; margin: 2px 0; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 10px; font-weight: 600; background: #d1fae5; color: #047857; }
        .b-violet { background: #ede9fe; color: #6d28d9; }
        .content p {
            margin: 0 0 9px;
            text-align: left;       /* hindari justify agar tidak "renggang panjang" */
            word-spacing: normal;
            white-space: normal;
        }
        .content p.lead { font-weight: 600; color: #064e3b; }
        .footer { margin-top: 28px; font-size: 10px; color: #9ca3af; text-align: center; border-top: 1px dashed #e5e7eb; padding-top: 8px; }
        .header-bar {
            background: linear-gradient(135deg, #10b981, #0d9488);
            color: white; padding: 12px 16px; border-radius: 10px;
            margin-bottom: 14px;
        }
        .header-bar .app { font-size: 12px; opacity: .9; letter-spacing: 1px; text-transform: uppercase; font-weight: 700; }
    </style>
</head>
<body>
    <div class="header-bar">
        <div class="app">{{ config('app.name', 'Eko-Scribe') }} — Materi Pembelajaran</div>
    </div>

    <h1>{{ $material->title }}</h1>
    <p class="meta">
        <span class="badge">{{ $material->level }}</span>
        @if($material->classroom)
            <span class="badge b-violet">{{ $material->classroom->name }}</span>
        @endif
        &middot; Topik: <strong>{{ $material->topic }}</strong>
    </p>
    <p class="meta">
        Disusun oleh: <strong>{{ $material->teacher->name ?? '—' }}</strong> &middot;
        Diunduh: {{ now()->isoFormat('D MMMM Y, HH:mm') }}
    </p>

    <h2>Konten Materi</h2>
    <div class="content">
        @forelse($paragraphs as $i => $par)
            @php
                // Dalam paragraf, bisa jadi ada baris-baris pendek (misal daftar);
                // gabungkan baris dengan spasi tunggal supaya tidak ada line break
                // yang bikin justify renggang.
                $par = trim(preg_replace('/\s*\n\s*/', ' ', $par));
            @endphp
            @if($par !== '')
                <p>{{ $par }}</p>
            @endif
        @empty
            <p><em>Materi belum memiliki konten.</em></p>
        @endforelse
    </div>

    <p class="footer">
        © {{ now()->year }} {{ config('app.name', 'Eko-Scribe') }} v{{ config('app.version') }} —
        Dokumen materi pembelajaran. Disebarluaskan untuk keperluan pendidikan.
    </p>
</body>
</html>

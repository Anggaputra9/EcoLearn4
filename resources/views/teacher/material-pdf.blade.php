<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $material->title }}</title>
    <style>
        @page { margin: 22mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; line-height: 1.6; }
        h1 { font-size: 22px; margin: 0 0 6px; color: #047857; }
        h2 { font-size: 14px; margin: 18px 0 8px; color: #0f766e; border-bottom: 1px solid #d1fae5; padding-bottom: 4px; }
        .meta { color: #6b7280; font-size: 11px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 10px; font-weight: 600; background: #d1fae5; color: #047857; }
        .b-violet { background: #ede9fe; color: #6d28d9; }
        .content { white-space: pre-wrap; margin-top: 10px; text-align: justify; }
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
    <div class="content">{{ $material->content }}</div>

    <p class="footer">
        © {{ now()->year }} {{ config('app.name', 'Eko-Scribe') }} v{{ config('app.version') }} —
        Dokumen materi pembelajaran. Disebarluaskan untuk keperluan pendidikan.
    </p>
</body>
</html>

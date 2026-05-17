{{--
    PDF Infografis 1 halaman A4 portrait. Layout:
    - Header bergradasi hijau dengan judul utama + foto kecil
    - Grid 2 kolom blok dengan ikon/foto, fakta kunci, statistik, kutipan
    - Footer ajakan aksi (CTA) berlatar hijau
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $material->title }} — Infografis</title>
    <style>
        @page { margin: 0; size: A4 portrait; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; margin: 0; }

        .wrap { width: 210mm; min-height: 297mm; padding: 0; }

        .hero {
            background: linear-gradient(135deg, #064e3b 0%, #0d9488 60%, #10b981 100%);
            color: #ffffff;
            padding: 16mm 16mm 14mm;
            position: relative;
        }
        .hero .badge {
            display: inline-block;
            background: rgba(255,255,255,0.18);
            color: #ecfdf5;
            font-size: 9pt;
            padding: 2pt 8pt;
            border-radius: 10pt;
            letter-spacing: 1pt;
            text-transform: uppercase;
            margin-bottom: 4mm;
        }
        .hero h1 {
            margin: 0;
            font-size: 28pt;
            font-weight: 800;
            line-height: 1.15;
        }
        .hero p.subtitle {
            margin: 3mm 0 0;
            font-size: 12pt;
            color: #d1fae5;
            line-height: 1.4;
            max-width: 70%;
        }
        .hero .cover-img {
            position: absolute;
            right: 16mm; top: 14mm;
            width: 45mm; height: 45mm;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.55);
            overflow: hidden;
        }
        .hero .cover-img img {
            width: 100%; height: 100%; object-fit: cover;
        }

        .meta {
            background: #ecfdf5;
            color: #064e3b;
            padding: 4mm 16mm;
            font-size: 10pt;
        }

        .blocks {
            display: table;
            width: 100%;
            padding: 8mm 12mm;
            border-spacing: 6mm;
        }
        .block-row { display: table-row; }
        .block {
            display: table-cell;
            width: 50%;
            background: #ffffff;
            border: 1px solid #d1fae5;
            border-radius: 8px;
            padding: 5mm 5mm 4mm;
            vertical-align: top;
        }
        .block .head {
            display: table; width: 100%; margin-bottom: 3mm;
        }
        .block .head .icon {
            display: table-cell;
            width: 14mm; vertical-align: middle;
        }
        .block .head .icon img {
            width: 14mm; height: 14mm;
            object-fit: cover;
            border-radius: 4px;
            border: 1.5px solid #10b981;
        }
        .block .head .num {
            display: table-cell;
            vertical-align: middle;
            padding-left: 4mm;
        }
        .block .head .num span {
            display: inline-block;
            font-size: 8pt;
            color: #047857;
            background: #d1fae5;
            padding: 1pt 6pt;
            border-radius: 8pt;
            font-weight: 700;
            letter-spacing: 0.5pt;
        }
        .block h3 {
            margin: 0 0 2mm;
            font-size: 13pt;
            color: #064e3b;
            line-height: 1.25;
        }
        .block p { margin: 0 0 2mm; font-size: 10pt; line-height: 1.4; color: #1f2937; }
        .block .stat {
            display: inline-block;
            font-size: 16pt;
            font-weight: 800;
            color: #10b981;
            margin-bottom: 1mm;
        }
        .block .quote {
            border-left: 3px solid #10b981;
            padding: 0 0 0 3mm;
            font-style: italic;
            color: #065f46;
            font-size: 9pt;
            margin-top: 2mm;
        }

        .cta {
            background: #064e3b;
            color: #ffffff;
            padding: 7mm 16mm;
            text-align: center;
            font-size: 12pt;
            line-height: 1.4;
        }
        .cta .label {
            display: inline-block;
            font-size: 9pt;
            color: #a7f3d0;
            letter-spacing: 1pt;
            text-transform: uppercase;
            margin-bottom: 2mm;
        }
        .cta strong { color: #fef3c7; }

        .footer {
            text-align: center;
            font-size: 8pt;
            color: #6b7280;
            padding: 4mm 16mm;
            border-top: 1px dashed #d1fae5;
        }
    </style>
</head>
<body>
    <div class="wrap">

        @php
            $title = $info['title'] ?: $material->title;
            $sub   = $info['subtitle'] ?: $material->topic;
            $blocks = $info['blocks'] ?? [];
        @endphp

        <div class="hero">
            <span class="badge">Infografis · {{ config('app.name', 'Eko-Scribe') }}</span>
            <h1>{{ $title }}</h1>
            <p class="subtitle">{{ $sub }}</p>
            @if ($headerData)
                <div class="cover-img">
                    <img src="{{ $headerData }}" alt="">
                </div>
            @endif
        </div>

        <div class="meta">
            <strong>Topik:</strong> {{ $material->topic }} &nbsp;·&nbsp;
            <strong>Tingkat:</strong> {{ $material->level }}
            @if ($material->classroom) &nbsp;·&nbsp; <strong>Kelas:</strong> {{ $material->classroom->name }} @endif
            &nbsp;·&nbsp; Disusun oleh: <strong>{{ $material->teacher->name ?? '—' }}</strong>
        </div>

        <div class="blocks">
            @php $rows = array_chunk($blocks, 2); @endphp
            @forelse ($rows as $row)
                <div class="block-row">
                    @foreach ($row as $idxInRow => $block)
                        @php $globalIdx = (array_search($row, $rows) * 2) + $idxInRow; @endphp
                        <div class="block">
                            <div class="head">
                                <div class="icon">
                                    @if (! empty($blockImages[$globalIdx]))
                                        <img src="{{ $blockImages[$globalIdx] }}" alt="">
                                    @else
                                        <div style="width:14mm;height:14mm;background:#d1fae5;border-radius:4px;"></div>
                                    @endif
                                </div>
                                <div class="num"><span>Blok {{ $globalIdx + 1 }}</span></div>
                            </div>
                            <h3>{{ $block['title'] }}</h3>
                            @if (! empty($block['fact']))
                                <p>{{ $block['fact'] }}</p>
                            @endif
                            @if (! empty($block['data']))
                                <div class="stat">{{ $block['data'] }}</div>
                            @endif
                            @if (! empty($block['quote']))
                                <div class="quote">"{{ $block['quote'] }}"</div>
                            @endif
                        </div>
                    @endforeach
                    @if (count($row) === 1)
                        <div class="block" style="border:1px dashed #d1fae5; background:#f0fdf4; color:#a7f3d0;"></div>
                    @endif
                </div>
            @empty
                <div class="block-row">
                    <div class="block">
                        <h3>Belum ada blok</h3>
                        <p>Format infografis belum tersedia untuk materi ini.</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if (! empty($info['cta']))
            <div class="cta">
                <span class="label">Ajakan Aksi</span><br>
                <strong>{{ $info['cta'] }}</strong>
            </div>
        @endif

        <div class="footer">
            © {{ now()->year }} {{ config('app.name', 'Eko-Scribe') }} —
            Infografis "{{ $material->title }}" diunduh {{ now()->isoFormat('D MMMM Y, HH:mm') }}
        </div>
    </div>
</body>
</html>

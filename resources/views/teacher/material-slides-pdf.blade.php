{{--
    PDF bergaya slide — 1 halaman = 1 slide (landscape A4).
    Layout: bar judul hijau, bullet di kiri, gambar di kanan,
    catatan pengajar dikutip kecil di bawah.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $material->title }} — Slide</title>
    <style>
        @page { margin: 0; size: A4 landscape; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; margin: 0; }

        .slide {
            position: relative;
            width: 100%;
            height: 210mm;            /* A4 landscape ≈ 297x210mm */
            page-break-after: always;
            padding: 14mm 16mm 12mm;
            background: #ffffff;
        }
        .slide:last-child { page-break-after: auto; }

        .slide.cover {
            background: linear-gradient(135deg, #064e3b 0%, #0d9488 50%, #10b981 100%);
            color: #ffffff;
        }
        .slide.cover .title { color: #ffffff; font-size: 40pt; line-height: 1.15; }
        .slide.cover .meta  { color: #d1fae5; font-size: 13pt; }

        .title {
            font-size: 26pt;
            font-weight: 700;
            color: #064e3b;
            margin: 0 0 4mm;
            line-height: 1.2;
        }
        .accent {
            width: 22mm; height: 1.6mm;
            background: #10b981;
            border-radius: 2px;
            margin-bottom: 6mm;
        }
        .row { display: table; width: 100%; height: calc(100% - 30mm); }
        .col-text, .col-img { display: table-cell; vertical-align: top; }
        .col-text { padding-right: 6mm; }
        .col-img { width: 95mm; }
        .col-img img {
            width: 95mm; height: 70mm;
            object-fit: cover;
            border: 2px solid #10b981;
            border-radius: 6px;
        }
        .full .col-text { padding-right: 0; }

        ul.bullets { list-style: none; padding: 0; margin: 0; }
        ul.bullets li {
            position: relative;
            padding: 0 0 5mm 7mm;
            font-size: 13pt;
            line-height: 1.45;
        }
        ul.bullets li:before {
            content: "•";
            color: #10b981;
            font-weight: 700;
            position: absolute;
            left: 0; top: -2pt;
            font-size: 18pt;
        }

        .notes {
            position: absolute;
            left: 16mm; right: 16mm; bottom: 8mm;
            padding-top: 3mm;
            border-top: 1px dashed #d1fae5;
            font-size: 9pt;
            color: #6b7280;
            font-style: italic;
        }
        .footer {
            position: absolute;
            right: 16mm; bottom: 4mm;
            font-size: 8pt;
            color: #9ca3af;
        }
        .cover-badge {
            display: inline-block;
            background: rgba(255,255,255,0.18);
            color: #ecfdf5;
            font-size: 11pt;
            padding: 3pt 10pt;
            border-radius: 12pt;
            margin-bottom: 8mm;
            letter-spacing: 1pt;
        }
    </style>
</head>
<body>
    @foreach ($slides as $i => $slide)
        @php
            $isCover = ($i === 0);
            $hasImg  = ! empty($imageData[$i]);
        @endphp

        <section class="slide {{ $isCover ? 'cover' : '' }}">
            @if ($isCover)
                <span class="cover-badge">{{ config('app.name', 'Eko-Scribe') }} · Slide</span>
                <h1 class="title">{{ $slide['title'] ?: $material->title }}</h1>
                <div class="accent" style="background:#a7f3d0;"></div>
                <p class="meta">
                    Topik: <strong>{{ $material->topic }}</strong> &nbsp;·&nbsp;
                    Tingkat: <strong>{{ $material->level }}</strong>
                    @if($material->classroom) &nbsp;·&nbsp; Kelas: <strong>{{ $material->classroom->name }}</strong> @endif
                </p>
                <p class="meta" style="margin-top: 4mm;">
                    @if(!empty($slide['bullets']))
                        {{ implode(' · ', $slide['bullets']) }}
                    @endif
                </p>
                @if($hasImg)
                    <div style="position:absolute; right:16mm; bottom:16mm; width:100mm; height:75mm;
                                border:2px solid rgba(255,255,255,0.5); border-radius:8px; overflow:hidden;">
                        <img src="{{ $imageData[$i] }}" alt="" style="width:100%; height:100%; object-fit:cover; opacity:0.9;">
                    </div>
                @endif
            @else
                <h2 class="title">{{ $slide['title'] }}</h2>
                <div class="accent"></div>

                <div class="row {{ $hasImg ? '' : 'full' }}">
                    <div class="col-text">
                        @if (! empty($slide['bullets']))
                            <ul class="bullets">
                                @foreach ($slide['bullets'] as $b)
                                    <li>{{ $b }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p style="color:#6b7280;"><em>Slide tanpa poin.</em></p>
                        @endif
                    </div>
                    @if ($hasImg)
                        <div class="col-img">
                            <img src="{{ $imageData[$i] }}" alt="">
                        </div>
                    @endif
                </div>

                @if (! empty($slide['notes']))
                    <div class="notes">
                        <strong>Catatan Pengajar:</strong> {{ $slide['notes'] }}
                    </div>
                @endif
            @endif

            <div class="footer">
                {{ $material->title }} &middot; Slide {{ $i + 1 }}/{{ count($slides) }}
            </div>
        </section>
    @endforeach
</body>
</html>

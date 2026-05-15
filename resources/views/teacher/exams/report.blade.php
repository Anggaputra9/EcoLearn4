<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $exam->title }}</title>
    <style>
        @page { margin: 22mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #047857; }
        h2 { font-size: 14px; margin: 18px 0 8px; color: #0f766e; border-bottom: 1px solid #d1fae5; padding-bottom: 4px; }
        .meta { color: #6b7280; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background: #f0fdf4; color: #065f46; }
        tr:nth-child(even) td { background: #fafafa; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 6px; font-size: 10px; font-weight: 600; }
        .b-green { background: #d1fae5; color: #047857; }
        .b-amber { background: #fef3c7; color: #92400e; }
        .b-rose  { background: #fee2e2; color: #991b1b; }
        .b-slate { background: #e5e7eb; color: #374151; }
        .summary { display: flex; gap: 16px; margin-top: 8px; }
        .card { flex: 1; border: 1px solid #e5e7eb; padding: 10px; border-radius: 6px; }
        .card .label { font-size: 10px; text-transform: uppercase; color: #6b7280; }
        .card .value { font-size: 22px; font-weight: 700; color: #047857; }
        .footer { margin-top: 26px; font-size: 10px; color: #9ca3af; text-align: center; }
        .answer { white-space: pre-wrap; padding: 6px 8px; background: #f9fafb; border-left: 3px solid #10b981; margin: 4px 0; font-size: 11px; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Laporan Ujian</h1>
    <p class="meta"><strong>{{ $exam->title }}</strong> · Materi: {{ $exam->material->title }} · Dibuat oleh {{ $exam->teacher->name ?? '—' }}</p>
    <p class="meta">Dicetak: {{ now()->isoFormat('D MMMM Y, HH:mm') }}</p>

    @php
        $attempts = $exam->attempts;
        $submitted = $attempts->where('status', 'submitted');
        $avg = round($submitted->whereNotNull('total_score')->avg('total_score') ?? 0, 1);
        $highest = $submitted->max('total_score');
        $lowest = $submitted->whereNotNull('total_score')->min('total_score');
    @endphp

    <h2>Ringkasan</h2>
    <div class="summary">
        <div class="card"><p class="label">Peserta</p><p class="value">{{ $attempts->count() }}</p></div>
        <div class="card"><p class="label">Rata-rata</p><p class="value">{{ $avg }}</p></div>
        <div class="card"><p class="label">Tertinggi</p><p class="value">{{ $highest ?? '—' }}</p></div>
        <div class="card"><p class="label">Terendah</p><p class="value">{{ $lowest ?? '—' }}</p></div>
    </div>

    <h2>Daftar Peserta</h2>
    <table>
        <thead>
        <tr>
            <th>#</th><th>Siswa</th><th>Status</th><th>Skor</th><th>Pelanggaran Tab</th><th>Mulai</th><th>Submit</th>
        </tr>
        </thead>
        <tbody>
        @foreach($exam->attempts as $i => $a)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $a->user->name ?? '—' }}</td>
                <td>
                    @php $cls = match($a->status){
                        'submitted' => 'b-green', 'in_progress' => 'b-amber',
                        'disqualified' => 'b-rose', default => 'b-slate'
                    }; @endphp
                    <span class="badge {{ $cls }}">{{ str_replace('_',' ',$a->status) }}</span>
                </td>
                <td><strong>{{ $a->total_score ?? '—' }}</strong>/100</td>
                <td>{{ $a->tab_switch_count }}</td>
                <td>{{ optional($a->started_at)->format('d-m H:i') ?? '—' }}</td>
                <td>{{ optional($a->submitted_at)->format('d-m H:i') ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @foreach($exam->attempts as $a)
        @if($a->submissions->isNotEmpty())
            <h2 style="page-break-before: always">Detail: {{ $a->user->name ?? '—' }}</h2>
            <p class="meta">Skor total: <strong>{{ $a->total_score ?? '—' }}</strong>/100 · Pelanggaran tab: {{ $a->tab_switch_count }}</p>
            @foreach($a->submissions as $i => $sub)
                <p><strong>Soal {{ $i + 1 }}.</strong> {{ $sub->question?->prompt_text }}</p>
                <div class="answer">{{ $sub->answer_text }}</div>
                <p class="meta">
                    Skor: <strong>{{ $sub->score ?? '—' }}</strong> ·
                    @if($sub->manually_graded) Manual @else Otomatis AI @endif
                </p>
                @if($sub->feedback)
                    <p class="meta"><em>"{{ $sub->feedback }}"</em></p>
                @endif
            @endforeach
        @endif
    @endforeach

    <p class="footer">© {{ now()->year }} {{ config('app.name') }} v{{ config('app.version') }} — Laporan otomatis.</p>
</body>
</html>

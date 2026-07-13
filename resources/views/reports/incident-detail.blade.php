<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $incident->incident_number }} — Laporan Insiden</title>
    <style>
        body { color: #111827; font: 14px/1.5 Arial, sans-serif; margin: 32px auto; max-width: 900px; }
        header { border-bottom: 3px solid #1f2937; display: flex; justify-content: space-between; padding-bottom: 16px; }
        h1, h2 { margin: 0; } h2 { font-size: 16px; margin-bottom: 10px; }
        section { break-inside: avoid; margin-top: 24px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; width: 22%; }
        .meta { color: #4b5563; font-size: 12px; text-align: right; }
        .pre { white-space: pre-wrap; }
        .toolbar { margin-bottom: 20px; text-align: right; }
        .toolbar button { background: #1d4ed8; border: 0; border-radius: 6px; color: white; cursor: pointer; padding: 10px 16px; }
        @media print { body { margin: 0; max-width: none; } .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
    <header>
        <div><h1>Laporan Insiden</h1><strong>{{ $incident->incident_number }}</strong></div>
        <div class="meta">Dibuat {{ now()->format('d M Y H:i') }}<br>Status: {{ strtoupper(str_replace('_', ' ', $incident->status)) }}</div>
    </header>

    <section>
        <h2>Informasi Utama</h2>
        <table>
            <tr><th>Judul</th><td>{{ $incident->title }}</td></tr>
            <tr><th>Kategori</th><td>{{ ucwords(str_replace('_', ' ', $incident->category)) }}</td></tr>
            <tr><th>Waktu Kejadian</th><td>{{ $incident->occurred_at?->format('d M Y H:i') }}</td></tr>
            <tr><th>Lokasi</th><td>{{ $incident->site?->name }}{{ $incident->area ? ' / '.$incident->area->name : '' }}{{ $incident->department ? ' / '.$incident->department->name : '' }}</td></tr>
            <tr><th>Reporter</th><td>{{ $incident->reporter?->name }}</td></tr>
            <tr><th>Klasifikasi</th><td>{{ $incident->severity?->name }} / {{ $incident->priority?->name }}</td></tr>
        </table>
    </section>

    <section><h2>Deskripsi</h2><div class="pre">{{ $incident->description }}</div></section>
    @if($incident->immediate_action)<section><h2>Tindakan Segera</h2><div class="pre">{{ $incident->immediate_action }}</div></section>@endif

    <section>
        <h2>Orang Terlibat</h2>
        @if($incident->involvedPersons->isEmpty())
            <p>Tidak ada orang terlibat yang dicatat.</p>
        @else
            <table><tr><th>Nama</th><th>Catatan</th></tr>
                @foreach($incident->involvedPersons as $person)<tr><td>{{ $person->name }}</td><td>{{ $person->pivot->note ?: '-' }}</td></tr>@endforeach
            </table>
        @endif
    </section>

    <section>
        <h2>Evidence ({{ $evidence->count() }})</h2>
        @if($evidence->isEmpty())<p>Belum ada evidence.</p>@else
            <table><tr><th>Nama File</th><th>Ukuran</th><th>Checksum SHA-256</th></tr>
                @foreach($evidence as $file)<tr><td>{{ $file->original_name }}</td><td>{{ number_format($file->size / 1024, 1) }} KB</td><td style="word-break:break-all">{{ $file->checksum }}</td></tr>@endforeach
            </table>
        @endif
    </section>

    <section>
        <h2>Riwayat Workflow</h2>
        <table><tr><th>Waktu</th><th>Aksi</th><th>Status</th><th>Alasan</th></tr>
            @foreach($history as $item)<tr><td>{{ $item->created_at?->format('d M Y H:i') }}</td><td>{{ $item->action_label }}</td><td>{{ $item->to_status }}</td><td>{{ $item->reason ?: '-' }}</td></tr>@endforeach
        </table>
    </section>
</body>
</html>

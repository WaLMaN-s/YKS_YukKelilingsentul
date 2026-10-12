<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
<style>
    body{font-family:Arial,Helvetica,sans-serif;color:#111;margin:24px}
    h1{margin:0 0 4px;font-size:20px} p{margin:0 0 16px;color:#555;font-size:13px}
    table{width:100%;border-collapse:collapse;font-size:12px} th,td{border:1px solid #ccc;padding:6px 8px;text-align:left} th{background:#f2f2f2}
    .bar{margin-bottom:16px} button{padding:8px 16px;cursor:pointer}
    @media print{.bar{display:none}}
</style></head>
<body>
<div class="bar"><button onclick="window.print()">Cetak / Simpan sebagai PDF</button></div>
<h1>{{ setting('site_name') }} — {{ $title }}</h1>
<p>Periode {{ tgl($start) }} s/d {{ tgl($end) }} · Dicetak {{ tgl(now(), 'd F Y H:i') }}</p>
<table>
    <thead><tr>@foreach ($report['headers'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
    <tbody>@forelse ($report['rows'] as $row)<tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($report['headers']) }}">Tidak ada data.</td></tr>@endforelse</tbody>
</table>
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body></html>

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request)
    {
        [$type, $range, $from, $to] = $this->params($request);
        [$start, $end] = $this->reports->range($range, $from, $to);

        return view('admin.reports.index', [
            'type' => $type,
            'range' => $range,
            'from' => $from ?: $start->toDateString(),
            'to' => $to ?: $end->toDateString(),
            'start' => $start,
            'end' => $end,
            'summary' => $this->reports->summary($start, $end),
            'report' => $this->reports->build($type, $start, $end),
        ]);
    }

    /** format=csv → unduh CSV (bisa dibuka di Excel). format=print → halaman cetak (Simpan sebagai PDF dari browser). */
    public function export(Request $request)
    {
        [$type, $range, $from, $to] = $this->params($request);
        [$start, $end] = $this->reports->range($range, $from, $to);
        $report = $this->reports->build($type, $start, $end);
        $title = 'Laporan '.ReportService::TYPES[$type];

        if ($request->query('format') === 'print') {
            return view('admin.reports.print', ['title' => $title, 'start' => $start, 'end' => $end, 'report' => $report]);
        }

        $filename = 'laporan-'.$type.'-'.$start->format('Ymd').'-'.$end->format('Ymd').'.csv';

        return new StreamedResponse(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, $report['headers'], ';');
            foreach ($report['rows'] as $row) {
                fputcsv($out, $row, ';');
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** @return array{0: string, 1: string, 2: ?string, 3: ?string} */
    private function params(Request $request): array
    {
        $request->validate([
            'type' => ['nullable', 'in:'.implode(',', array_keys(ReportService::TYPES))],
            'range' => ['nullable', 'in:'.implode(',', array_keys(ReportService::RANGES))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            $request->query('type', 'pendapatan'),
            $request->query('range', 'month'),
            $request->query('from'),
            $request->query('to'),
        ];
    }
}

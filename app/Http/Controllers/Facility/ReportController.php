<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Services\FacilityReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __invoke(Request $request, FacilityReportService $reports)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'export' => ['nullable', 'in:csv'],
        ], ['to.after_or_equal' => 'The end date must be on or after the start date.']);

        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();

        $facility = $request->user()->facility;
        $report = $reports->summary($facility, $from, $to);

        if ($request->input('export') === 'csv') {
            return $this->exportCsv($facility, $from, $to, $report);
        }

        return view('facility.reports', [
            'facility' => $facility,
            'from' => $from,
            'to' => $to,
            'report' => $report,
        ]);
    }

    private function exportCsv($facility, $from, $to, array $report): StreamedResponse
    {
        $filename = 'facility-report-'.$facility->code.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        $callback = function () use ($facility, $from, $to, $report) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Facility Report']);
            fputcsv($handle, ['Facility', $facility->name]);
            fputcsv($handle, ['From', $from->toDateString()]);
            fputcsv($handle, ['To', $to->toDateString()]);
            fputcsv($handle, []);

            fputcsv($handle, ['Summary']);
            fputcsv($handle, ['Passports issued', $report['passports_issued']]);
            fputcsv($handle, ['Passports opened', $report['passports_opened']]);
            fputcsv($handle, ['Scan rate %', $report['scan_rate']]);
            fputcsv($handle, ['Visits', $report['visits']]);
            fputcsv($handle, ['Visits completed', $report['visits_completed']]);
            fputcsv($handle, ['Vaccinations', $report['vaccinations']]);
            fputcsv($handle, []);

            fputcsv($handle, ['Visits per day']);
            fputcsv($handle, ['Date', 'Visits']);
            foreach ($report['daily_visits'] as $day => $total) {
                fputcsv($handle, [$day, $total]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Top diagnoses']);
            fputcsv($handle, ['Diagnosis', 'Visits']);
            foreach (Collection::make($report['top_diagnoses']) as $row) {
                fputcsv($handle, [$row['diagnosis'] ?? '', $row['visits'] ?? 0]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}

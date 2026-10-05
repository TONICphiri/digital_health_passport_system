<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Services\FacilityReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request, FacilityReportService $reports): View
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ], ['to.after_or_equal' => 'The end date must be on or after the start date.']);

        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();

        return view('facility.reports', [
            'facility' => $request->user()->facility,
            'from' => $from,
            'to' => $to,
            'report' => $reports->summary($request->user()->facility, $from, $to),
        ]);
    }
}

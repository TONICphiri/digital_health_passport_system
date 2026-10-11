<?php

namespace App\Services;

use App\Enums\AccessMethod;
use App\Enums\VisitStageType;
use App\Enums\VisitStatus;
use App\Models\Facility;
use App\Models\PassportAccess;
use App\Models\Patient;
use App\Models\Vaccination;
use App\Models\Visit;
use App\Models\VisitStage;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Summary figures for the facility report page, for a chosen date range.
 */
class FacilityReportService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Facility $facility, Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $visits = Visit::query()->where('facility_id', $facility->id)->whereBetween('started_at', [$start, $end]);
        $accesses = PassportAccess::query()->where('facility_id', $facility->id)->whereBetween('opened_at', [$start, $end]);

        $opened = (clone $accesses)->count();
        $scanned = (clone $accesses)->where('method', AccessMethod::QrScan)->count();

        return [
            'passports_issued' => Patient::query()->where('registered_facility_id', $facility->id)->whereBetween('created_at', [$start, $end])->count(),
            'passports_opened' => $opened,
            'opened_by_scan' => $scanned,
            'scan_rate' => $opened > 0 ? round($scanned / $opened * 100) : 0,
            'visits' => (clone $visits)->count(),
            'visits_completed' => (clone $visits)->where('status', VisitStatus::Completed)->count(),
            'vaccinations' => Vaccination::query()->where('facility_id', $facility->id)->whereBetween('administered_on', [$start->toDateString(), $end->toDateString()])->count(),
            // A diagnosis is the summary the doctor writes during consultation.
            'top_diagnoses' => VisitStage::query()
                ->where('type', VisitStageType::Consultation)
                ->whereNotNull('summary')
                ->whereHas('visit', fn ($query) => $query->where('facility_id', $facility->id)->whereBetween('started_at', [$start, $end]))
                ->select('summary as diagnosis', DB::raw('COUNT(*) as total'))
                ->groupBy('summary')->orderByDesc('total')->limit(10)->get(),
            'daily_visits' => $this->everyDay($start, $end, (clone $visits)
                ->select(DB::raw('DATE(started_at) as day'), DB::raw('COUNT(*) as total'))
                ->groupBy('day')->pluck('total', 'day')),
        ];
    }

    /**
     * Include days without entries so the chart shows the whole period.
     *
     * @param  Collection<string, int>  $totals
     * @return Collection<string, int>
     */
    private function everyDay(Carbon $start, Carbon $end, Collection $totals): Collection
    {
        return collect(CarbonPeriod::create($start->copy()->startOfDay(), '1 day', $end->copy()->startOfDay()))
            ->mapWithKeys(fn (Carbon $day) => [$day->toDateString() => (int) ($totals[$day->toDateString()] ?? 0)]);
    }
}

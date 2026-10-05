<?php

namespace App\Services;

use App\Enums\AccessMethod;
use App\Models\Encounter;
use App\Models\Facility;
use App\Models\PassportAccess;
use App\Models\Patient;
use App\Models\Vaccination;
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

        $encounters = Encounter::query()->where('facility_id', $facility->id)->whereBetween('recorded_at', [$start, $end]);
        $accesses = PassportAccess::query()->where('facility_id', $facility->id)->whereBetween('opened_at', [$start, $end]);

        $opened = (clone $accesses)->count();
        $scanned = (clone $accesses)->where('method', AccessMethod::QrScan)->count();

        return [
            'passports_issued' => Patient::query()->where('registered_facility_id', $facility->id)->whereBetween('created_at', [$start, $end])->count(),
            'passports_opened' => $opened,
            'opened_by_scan' => $scanned,
            'scan_rate' => $opened > 0 ? round($scanned / $opened * 100) : 0,
            'encounters' => (clone $encounters)->count(),
            'vaccinations' => Vaccination::query()->where('facility_id', $facility->id)->whereBetween('administered_on', [$start->toDateString(), $end->toDateString()])->count(),
            'top_diagnoses' => (clone $encounters)->whereNotNull('diagnosis')
                ->select('diagnosis', DB::raw('COUNT(*) as total'))
                ->groupBy('diagnosis')->orderByDesc('total')->limit(10)->get(),
            'daily_encounters' => $this->everyDay($start, $end, (clone $encounters)
                ->select(DB::raw('DATE(recorded_at) as day'), DB::raw('COUNT(*) as total'))
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

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The facility report page and its CSV download.
 */
class FacilityReportsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function facilityAdmin(): User
    {
        return User::query()->where('email', 'facility@healthpassport.mw')->firstOrFail();
    }

    public function test_the_report_page_opens_for_a_facility_administrator(): void
    {
        $this->actingAs($this->facilityAdmin())
            ->get(route('facility.reports'))
            ->assertOk()
            ->assertSee('Reports');
    }

    public function test_the_csv_export_lists_diagnoses_and_their_visit_counts(): void
    {
        $response = $this->actingAs($this->facilityAdmin())
            ->get(route('facility.reports', ['export' => 'csv']));

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Facility Report', $csv);
        $this->assertStringContainsString('Top diagnoses', $csv);
        $this->assertStringContainsString('Diagnosis,Visits', $csv);

        // The grouped query returns objects with the count aliased as "total".
        // A blank diagnosis or a missing count means the export is broken.
        $this->assertMatchesRegularExpression('/^"Chest infection",\d+$/m', $csv);
    }

    public function test_a_facility_administrator_without_a_facility_is_forbidden(): void
    {
        $admin = $this->facilityAdmin();
        $admin->forceFill(['facility_id' => null])->save();

        $this->actingAs($admin)
            ->get(route('facility.reports'))
            ->assertForbidden();
    }
}

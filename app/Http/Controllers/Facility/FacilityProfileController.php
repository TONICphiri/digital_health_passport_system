<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Contact details a Facility Administrator may update. The name, code, type
 * and district are controlled by the System Administrator.
 */
class FacilityProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('facility.profile', ['facility' => $request->user()->facility->load('district')]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $facility = $request->user()->facility;

        $facility->update($request->validate([
            'physical_address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
        ]));

        $audit->record('facility.profile-updated', "Updated the contact details of {$facility->name}.", $facility);

        return back()->with('success', 'The facility details have been saved.');
    }
}

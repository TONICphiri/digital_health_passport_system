<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vaccine;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The national vaccine list used by every facility.
 */
class VaccineController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function index(): View
    {
        return view('admin.vaccines.index', [
            'vaccines' => Vaccine::query()->withCount('vaccinations')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.vaccines.form', ['vaccine' => new Vaccine(['total_doses' => 1, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vaccine = Vaccine::create($this->validated($request));
        $this->audit->record('vaccine.created', "Added {$vaccine->name} to the vaccine list.", $vaccine);

        return redirect()->route('admin.vaccines.index')->with('success', "{$vaccine->name} has been added.");
    }

    public function edit(Vaccine $vaccine): View
    {
        return view('admin.vaccines.form', ['vaccine' => $vaccine]);
    }

    public function update(Request $request, Vaccine $vaccine): RedirectResponse
    {
        $vaccine->update($this->validated($request, $vaccine));
        $this->audit->record('vaccine.updated', "Updated {$vaccine->name}.", $vaccine);

        return redirect()->route('admin.vaccines.index')->with('success', "{$vaccine->name} has been updated.");
    }

    private function validated(Request $request, ?Vaccine $vaccine = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('vaccines')->ignore($vaccine)],
            'protects_against' => ['required', 'string', 'max:255'],
            'total_doses' => ['required', 'integer', 'min:1', 'max:10'],
            'days_between_doses' => ['nullable', 'required_unless:total_doses,1', 'integer', 'min:1', 'max:3650'],
            'recommended_age' => ['nullable', 'string', 'max:100'],
        ], [
            'days_between_doses.required_unless' => 'Enter the number of days between doses for a vaccine with more than one dose.',
        ]);

        return [...$data, 'is_active' => $request->boolean('is_active')];
    }
}

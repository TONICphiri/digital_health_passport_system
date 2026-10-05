<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FacilityStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\FacilityRequest;
use App\Models\District;
use App\Models\Facility;
use App\Services\AuditLogger;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacilityController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SettingService $settings,
    ) {
    }

    public function index(Request $request): View
    {
        $facilities = Facility::query()
            ->with('district')
            ->withCount(['users', 'patients'])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('code', 'like', '%'.$request->input('search').'%')))
            ->when($request->filled('district'), fn ($query) => $query->where('district_id', $request->integer('district')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderBy('name')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('admin.facilities.index', [
            'facilities' => $facilities,
            'districts' => District::query()->orderBy('name')->get(),
            'statuses' => FacilityStatus::options(),
        ]);
    }

    public function create(): View
    {
        return view('admin.facilities.form', $this->formData(new Facility));
    }

    public function store(FacilityRequest $request): RedirectResponse
    {
        $facility = Facility::create([...$request->validated(), 'status' => FacilityStatus::Active]);
        $this->audit->record('facility.created', "Registered {$facility->name}.", $facility);

        return redirect()->route('admin.facilities.show', $facility)
            ->with('success', "{$facility->name} has been registered. Next, create its Facility Administrator account.");
    }

    public function show(Facility $facility): View
    {
        $facility->load('district')->loadCount(['patients', 'encounters', 'users']);

        return view('admin.facilities.show', [
            'facility' => $facility,
            'administrators' => $facility->users()->withRole(RoleName::FacilityAdmin)->get(),
            'staffByRole' => $facility->users()->with('roles')->get()
                ->groupBy(fn ($user) => $user->roleLabel())->map->count(),
        ]);
    }

    public function edit(Facility $facility): View
    {
        return view('admin.facilities.form', $this->formData($facility));
    }

    public function update(FacilityRequest $request, Facility $facility): RedirectResponse
    {
        $facility->update($request->validated());
        $this->audit->record('facility.updated', "Updated the details of {$facility->name}.", $facility);

        return redirect()->route('admin.facilities.show', $facility)->with('success', 'The facility details have been saved.');
    }

    public function toggleStatus(Facility $facility): RedirectResponse
    {
        $facility->update([
            'status' => $facility->isActive() ? FacilityStatus::Inactive : FacilityStatus::Active,
        ]);

        $state = $facility->isActive() ? 'activated' : 'deactivated';
        $this->audit->record('facility.status-changed', "{$facility->name} was {$state}.", $facility);

        return back()->with('success', "{$facility->name} has been {$state}.");
    }

    private function formData(Facility $facility): array
    {
        return [
            'facility' => $facility,
            'districts' => District::query()->orderBy('name')->get(),
            'facilityTypes' => $this->settings->list('facility_types'),
            'ownershipTypes' => $this->settings->list('ownership_types'),
        ];
    }
}

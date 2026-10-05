<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DistrictController extends Controller
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    public function index(): View
    {
        return view('admin.districts.index', [
            'districts' => District::query()->withCount('facilities')->orderBy('region')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.districts.form', ['district' => new District, 'regions' => $this->settings->list('regions')]);
    }

    public function store(Request $request): RedirectResponse
    {
        District::create($this->validated($request));

        return redirect()->route('admin.districts.index')->with('success', 'The district has been added.');
    }

    public function edit(District $district): View
    {
        return view('admin.districts.form', ['district' => $district, 'regions' => $this->settings->list('regions')]);
    }

    public function update(Request $request, District $district): RedirectResponse
    {
        $district->update($this->validated($request, $district));

        return redirect()->route('admin.districts.index')->with('success', 'The district has been updated.');
    }

    private function validated(Request $request, ?District $district = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('districts')->ignore($district)],
            'region' => ['required', Rule::in($this->settings->list('regions'))],
        ]);
    }
}

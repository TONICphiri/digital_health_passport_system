<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\StaffAccountRequest;
use App\Models\Facility;
use App\Models\User;
use App\Services\StaffAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacilityAdministratorController extends Controller
{
    public function __construct(private readonly StaffAccountService $accounts)
    {
    }

    public function index(Request $request): View
    {
        $users = User::query()
            ->withRole(RoleName::FacilityAdmin)
            ->with('facility')
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('email', 'like', '%'.$request->input('search').'%')))
            ->when($request->filled('facility'), fn ($query) => $query->where('facility_id', $request->integer('facility')))
            ->orderBy('name')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('admin.facility-administrators.index', [
            'users' => $users,
            'facilities' => Facility::query()->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.facility-administrators.form', [
            'user' => new User(['facility_id' => $request->integer('facility') ?: null]),
            'facilities' => Facility::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StaffAccountRequest $request): RedirectResponse
    {
        $result = $this->accounts->create($request->accountData(), RoleName::FacilityAdmin);

        return redirect()->route('admin.facility-administrators.index')
            ->with('success', "The account for {$result['user']->name} has been created.")
            ->with('temporary_password', ['name' => $result['user']->name, 'email' => $result['user']->email, 'password' => $result['temporary_password']]);
    }

    public function edit(User $user): View
    {
        $this->authorize('manage', $user);

        return view('admin.facility-administrators.form', [
            'user' => $user,
            'facilities' => Facility::query()->orderBy('name')->get(),
        ]);
    }

    public function update(StaffAccountRequest $request, User $user): RedirectResponse
    {
        $this->authorize('manage', $user);
        $this->accounts->update($user, $request->accountData());

        return redirect()->route('admin.facility-administrators.index')->with('success', 'The account has been updated.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->authorize('manage', $user);
        $this->accounts->toggleStatus($user);

        return back()->with('success', "The account for {$user->name} is now ".($user->isActive() ? 'active' : 'inactive').'.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorize('manage', $user);
        $password = $this->accounts->resetPassword($user);

        return back()->with('success', "The password for {$user->name} has been reset.")
            ->with('temporary_password', ['name' => $user->name, 'email' => $user->email, 'password' => $password]);
    }
}

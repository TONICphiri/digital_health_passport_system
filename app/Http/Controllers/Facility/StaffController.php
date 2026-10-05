<?php

namespace App\Http\Controllers\Facility;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\StaffAccountRequest;
use App\Models\User;
use App\Services\StaffAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Health worker accounts at the Facility Administrator's own facility.
 */
class StaffController extends Controller
{
    public function __construct(private readonly StaffAccountService $accounts)
    {
    }

    public function index(Request $request): View
    {
        $staff = User::query()
            ->where('facility_id', $request->user()->facility_id)
            ->withRole(RoleName::HealthWorker)
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('email', 'like', '%'.$request->input('search').'%')))
            ->orderBy('name')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('facility.staff.index', ['staff' => $staff]);
    }

    public function create(): View
    {
        return view('facility.staff.form', ['user' => new User]);
    }

    public function store(StaffAccountRequest $request): RedirectResponse
    {
        $result = $this->accounts->create(
            [...$request->accountData(), 'facility_id' => $request->user()->facility_id],
            RoleName::HealthWorker,
        );

        return redirect()->route('facility.staff.index')
            ->with('success', "The account for {$result['user']->name} has been created.")
            ->with('temporary_password', ['name' => $result['user']->name, 'email' => $result['user']->email, 'password' => $result['temporary_password']]);
    }

    public function edit(User $user): View
    {
        $this->authorize('manage', $user);

        return view('facility.staff.form', ['user' => $user]);
    }

    public function update(StaffAccountRequest $request, User $user): RedirectResponse
    {
        $this->authorize('manage', $user);
        $this->accounts->update($user, $request->accountData(), RoleName::HealthWorker);

        return redirect()->route('facility.staff.index')->with('success', 'The account has been updated.');
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

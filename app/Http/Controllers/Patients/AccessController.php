<?php

namespace App\Http\Controllers\Patients;

use App\Http\Controllers\Controller;
use App\Models\PassportAccess;
use App\Services\PassportAccessService;
use App\Support\OfflineForms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The point where every visit begins. A health worker scans the QR code on the
 * passport card, which opens the passport for a limited time. Nothing in a
 * passport can be read or changed without this step.
 */
class AccessController extends Controller
{
    public function __construct(private readonly PassportAccessService $access)
    {
    }

    public function create(Request $request): View
    {
        $open = PassportAccess::query()
            ->with('patient')
            ->where('user_id', $request->user()->id)
            ->open()
            ->latest('opened_at')
            ->limit(10)
            ->get();

        return view('patients.scan', ['openPassports' => $open, ...OfflineForms::data()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'required_without:identifier', 'string', 'max:120'],
            'identifier' => ['nullable', 'required_without:code', 'string', 'max:40'],
            'date_of_birth' => ['nullable', 'required_with:identifier', 'date', 'before_or_equal:today'],
        ], [
            'code.required_without' => 'Scan the card, or enter the passport number and date of birth.',
            'identifier.required_without' => 'Enter the passport number or National ID.',
            'date_of_birth.required_with' => 'Enter the date of birth of the passport holder.',
        ], ['identifier' => 'passport number or National ID']);

        $access = filled($data['code'] ?? null)
            ? $this->access->openByScan($data['code'], $request->user())
            : $this->access->openByIdentity($data['identifier'], $data['date_of_birth'], $request->user());

        return redirect()->route('patients.show', $access->patient);
    }

    public function destroy(Request $request, PassportAccess $access): RedirectResponse
    {
        abort_unless($access->user_id === $request->user()->id, 403);

        $this->access->close($access);

        return redirect()->route('patients.scan')->with('success', 'The passport has been closed.');
    }
}

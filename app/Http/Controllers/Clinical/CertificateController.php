<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Models\Vaccination;
use App\Services\AuditLogger;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Vaccination certificates. The printed certificate carries a QR code that
 * points to a signed address, so anyone can check that it is genuine without
 * signing in and without seeing more than the vaccine, the date and a first name.
 */
class CertificateController extends Controller
{
    public function show(Vaccination $vaccination, QrCodeService $qr, AuditLogger $audit): View
    {
        $patient = $vaccination->patient;
        $this->authorize('view', $patient);

        $vaccination->load(['vaccine', 'facility', 'administeredBy']);
        $audit->record('certificate.issued', "Printed a vaccination certificate for {$patient->full_name}.", $patient);

        $back = auth()->user()->ownsPatientRecord($patient)
            ? route('portal.records', $patient->id === auth()->user()->patient_id ? [] : ['patient' => $patient->id])
            : route('patients.show', $patient);

        return view('certificates.show', [
            'vaccination' => $vaccination,
            'patient' => $patient,
            'qrCode' => $qr->forUrl(URL::signedRoute('certificates.verify', $vaccination), 120),
            'back' => $back,
        ]);
    }

    /**
     * Public check. The route is signed, so a changed or invented address is refused.
     */
    public function verify(Vaccination $vaccination): View
    {
        $vaccination->load(['vaccine', 'facility', 'patient']);

        return view('certificates.verify', ['vaccination' => $vaccination]);
    }
}

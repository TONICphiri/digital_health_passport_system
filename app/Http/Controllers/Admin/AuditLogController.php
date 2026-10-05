<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The activity log. The System Administrator sees every facility; a
 * Facility Administrator sees only activity at their own facility.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $logs = AuditLog::query()
            ->with(['user', 'facility'])
            ->when(! $user->isRole(RoleName::SystemAdmin), fn ($query) => $query->where('facility_id', $user->facility_id))
            ->when($request->filled('search'), fn ($query) => $query->where('description', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.audit-log', ['logs' => $logs]);
    }
}
